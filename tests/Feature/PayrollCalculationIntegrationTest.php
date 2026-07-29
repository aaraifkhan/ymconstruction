<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\ApprovePayrollVariableComponentAction;
use App\Actions\Payroll\GeneratePayrollEntriesAction;
use App\Actions\Payroll\PostPayrollRunAction;
use App\Actions\Payroll\ReversePayrollRunAction;
use App\Actions\Payroll\SubmitPayrollRunAction;
use App\Actions\Payroll\SubmitPayrollVariableComponentAction;
use App\Enums\AccountingProfile;
use App\Enums\AttendanceSummaryStatus;
use App\Enums\CompensationStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmploymentCategory;
use App\Enums\PayrollAccountComponent;
use App\Enums\PayrollComponentType;
use App\Enums\PayrollVariableComponentStatus;
use App\Filament\Pages\PayrollReports;
use App\Filament\Resources\PayrollCalculationRules\Pages\ListPayrollCalculationRules;
use App\Filament\Resources\PayrollVariableComponents\Pages\ListPayrollVariableComponents;
use App\Models\AttendanceMonthlySummary;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\PayrollAccountMapping;
use App\Models\PayrollCalculationRule;
use App\Models\PayrollRun;
use App\Models\PayrollVariableComponent;
use App\Models\User;
use App\Reports\PayrollSummaryReport;
use App\Reports\SalaryRegisterReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollCalculationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_generation_uses_approved_sources_and_is_deterministic(): void
    {
        [$company, $employment, $run, $maker] = $this->payrollContext(31000);
        $approver = User::factory()->create();
        $this->authenticate($approver, $company, [
            'Approve:PayrollVariableComponent',
            'Approve:PayrollRun',
        ]);

        PayrollCalculationRule::factory()->forCompany($company)->create([
            'effective_from' => '2026-01-01',
        ]);
        AttendanceMonthlySummary::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'status' => AttendanceSummaryStatus::Finalized,
            'scheduled_days' => 31,
            'scheduled_minutes' => 14880,
            'present_days' => 27,
            'absent_days' => 1,
            'half_days' => 1,
            'late_minutes' => 480,
            'unpaid_leave_days' => 2,
            'source_checksum' => hash('sha256', 'attendance-july'),
            'finalized_by_id' => $approver->getKey(),
            'finalized_at' => now(),
        ]);
        $variable = PayrollVariableComponent::factory()->forEmployment($employment)->create([
            'amount' => 3100,
            'created_by_id' => $maker->getKey(),
        ]);

        $this->authenticate($maker, $company, [
            'GenerateEntries:PayrollRun',
            'Submit:PayrollVariableComponent',
        ]);
        app(SubmitPayrollVariableComponentAction::class)->handle($variable, $maker);
        $this->expectIndependentApprover($variable, $maker);
        app(ApprovePayrollVariableComponentAction::class)->handle($variable->fresh(), $approver);

        $generated = app(GeneratePayrollEntriesAction::class)->handle($run, $maker);
        $entry = $generated->entries->sole();
        $firstChecksum = $generated->source_checksum;
        $firstKeys = $entry->components->pluck('idempotency_key')->sort()->values()->all();

        $this->assertSame(PayrollVariableComponentStatus::Approved, $variable->fresh()->status);
        $this->assertEqualsWithDelta(34100, (float) $entry->gross_salary, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $entry->absence_deduction, 0.01);
        $this->assertEqualsWithDelta(2000, (float) $entry->unpaid_leave_deduction, 0.01);
        $this->assertEqualsWithDelta(500, (float) $entry->half_day_deduction, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $entry->late_deduction, 0.01);
        $this->assertEqualsWithDelta(29600, (float) $entry->net_salary, 0.01);
        $this->assertEqualsCanonicalizing([
            PayrollComponentType::PayableBasic,
            PayrollComponentType::Bonus,
            PayrollComponentType::AbsenceDeduction,
            PayrollComponentType::UnpaidLeaveDeduction,
            PayrollComponentType::LateDeduction,
            PayrollComponentType::HalfDayDeduction,
        ], $entry->components->pluck('type')->all());

        $regenerated = app(GeneratePayrollEntriesAction::class)->handle($run->fresh(), $maker);
        $this->assertSame($firstChecksum, $regenerated->source_checksum);
        $this->assertSame($firstKeys, $regenerated->entries->sole()->components
            ->pluck('idempotency_key')->sort()->values()->all());
        $this->assertSame(2, $regenerated->generation_revision);

        $summary = app(PayrollSummaryReport::class)->forCompany($company)->sole();
        $register = app(SalaryRegisterReport::class)->forCompany($company)->sole();
        $this->assertSame('34100.0000', $summary['gross']);
        $this->assertSame('4500.0000', $summary['attendance_deductions']);
        $this->assertSame('29600', (string) $register['net']);

        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        foreach ([
            PayrollAccountComponent::BasicSalary,
            PayrollAccountComponent::Bonus,
            PayrollAccountComponent::AbsenceDeduction,
            PayrollAccountComponent::UnpaidLeaveDeduction,
            PayrollAccountComponent::LateDeduction,
            PayrollAccountComponent::HalfDayDeduction,
        ] as $component) {
            $this->mapPayroll($company, $component);
        }
        $poster = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $this->authenticate($poster, $company, []);
        app(SubmitPayrollRunAction::class)->handle($regenerated, $maker);
        app(ApprovePayrollRunAction::class)->handle($regenerated->fresh(), $approver);
        $posted = app(PostPayrollRunAction::class)->handle($regenerated->fresh(), $poster);
        $this->assertSame('34100.0000', $posted->journalEntry->debit_total);
        $this->assertSame('34100.0000', $posted->journalEntry->credit_total);

        $this->expectException(ValidationException::class);
        $posted->entries()->sole()->components->first()->update(['amount' => 1]);
    }

    public function test_required_attendance_is_atomic_when_summary_is_missing(): void
    {
        [$company, , $run, $maker] = $this->payrollContext();
        PayrollCalculationRule::factory()->forCompany($company)->create();

        try {
            app(GeneratePayrollEntriesAction::class)->handle($run, $maker);
            $this->fail('Finalized attendance must be required by the effective rule.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertSame(0, $run->entries()->count());
    }

    public function test_financing_recovery_posts_once_and_reversal_restores_the_schedule(): void
    {
        [$company, $employment, $run, $maker] = $this->payrollContext();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $role = Role::findOrCreate('super_admin');
        $approver = User::factory()->create()->assignRole($role);
        $poster = User::factory()->create()->assignRole($role);
        $maker->assignRole($role);
        foreach ([$maker, $approver, $poster] as $user) {
            $user->companies()->syncWithoutDetaching([
                $company->getKey() => ['is_active' => true, 'can_access_descendants' => false],
            ]);
        }

        $financing = EmployeeFinancing::factory()->forCompany($company)->create([
            'employment_id' => $employment->getKey(),
            'status' => EmployeeFinancingStatus::Active,
            'principal_amount' => 4000,
            'total_repayable' => 4000,
            'installment_count' => 1,
            'first_due_date' => '2026-07-31',
            'approved_by_id' => $approver->getKey(),
            'approved_at' => now(),
            'disbursed_at' => now(),
        ]);
        $installment = EmployeeFinancingInstallment::factory()->create([
            'company_id' => $company->getKey(),
            'employee_financing_id' => $financing->getKey(),
            'due_date' => '2026-07-31',
            'principal_due' => 4000,
            'total_due' => 4000,
        ]);

        app(GeneratePayrollEntriesAction::class)->handle($run, $maker);
        $entry = $run->entries()->sole();
        $this->assertSame('4000.0000', (string) $entry->loan_advance_deduction);
        $this->assertSame(EmployeeFinancing::class, $entry->components()
            ->where('type', PayrollComponentType::AdvanceRecovery)->sole()->source_type);

        app(SubmitPayrollRunAction::class)->handle($run, $maker);
        app(ApprovePayrollRunAction::class)->handle($run, $approver);
        $this->mapPayroll($company, PayrollAccountComponent::BasicSalary);
        $posted = app(PostPayrollRunAction::class)->handle($run, $poster);
        app(PostPayrollRunAction::class)->handle($posted->fresh(), $poster);

        $this->assertEqualsWithDelta(4000, (float) $financing->transactions()
            ->where('type', 'payroll_recovery')->sum('total_amount'), 0.0001);
        $this->assertSame('0.0000', $installment->fresh()->outstandingAmount());
        $this->assertSame(1, $company->journalEntries()->where('source_type', PayrollRun::class)->count());

        app(ReversePayrollRunAction::class)->handle(
            $posted->fresh(),
            $poster,
            CarbonImmutable::parse('2026-07-31'),
            'Reverse source-backed payroll recovery.',
        );

        $this->assertSame('4000.0000', $installment->fresh()->outstandingAmount());
        $this->assertSame(EmployeeFinancingStatus::Active, $financing->fresh()->status);
        $this->assertSame(1, $financing->transactions()->where('type', 'reversal')->count());
    }

    public function test_hr7_tenant_pages_render_for_an_authorized_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $this->authenticate($user, $company, []);

        Livewire::test(ListPayrollCalculationRules::class)->assertOk();
        Livewire::test(ListPayrollVariableComponents::class)->assertOk();
        Livewire::test(PayrollReports::class)->assertOk();
    }

    /**
     * @return array{Company, Employment, PayrollRun, User}
     */
    private function payrollContext(float $basicSalary = 31000): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
            'employment_category' => EmploymentCategory::AdministrativeStaff,
        ]);
        EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'status' => CompensationStatus::Approved,
            'effective_from' => '2026-01-01',
            'basic_salary' => $basicSalary,
            'house_travel_allowance' => 0,
            'food_allowance' => 0,
            'other_allowance' => 0,
            'approved_at' => now(),
        ]);
        $maker = User::factory()->create();
        $run = PayrollRun::factory()->create([
            'company_id' => $company->getKey(),
            'created_by_id' => $maker->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ]);
        $this->authenticate($maker, $company, [
            'GenerateEntries:PayrollRun',
            'Submit:PayrollRun',
            'Approve:PayrollRun',
            'Post:PayrollRun',
            'Reverse:PayrollRun',
        ]);

        return [$company, $employment, $run, $maker];
    }

    private function expectIndependentApprover(PayrollVariableComponent $variable, User $maker): void
    {
        $maker->givePermissionTo(Permission::findOrCreate('Approve:PayrollVariableComponent'));

        try {
            app(ApprovePayrollVariableComponentAction::class)->handle($variable->fresh(), $maker);
            $this->fail('Maker must not approve their own variable payroll source.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
    }

    private function mapPayroll(Company $company, PayrollAccountComponent $component): void
    {
        PayrollAccountMapping::query()->create([
            'company_id' => $company->getKey(),
            'component' => $component,
            'account_id' => $company->accounts()->where('code', '5100')->firstOrFail()->getKey(),
            'is_active' => true,
        ]);
    }

    /** @param array<int, string> $permissions */
    private function authenticate(User $user, Company $company, array $permissions): void
    {
        $user->companies()->syncWithoutDetaching([
            $company->getKey() => ['is_active' => true, 'can_access_descendants' => false],
        ]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
