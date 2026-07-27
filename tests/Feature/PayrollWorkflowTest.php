<?php

namespace Tests\Feature;

use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\GeneratePayrollEntriesAction;
use App\Actions\Payroll\SubmitPayrollRunAction;
use App\Enums\CompensationStatus;
use App\Enums\EmploymentCategory;
use App\Enums\PayrollRunStatus;
use App\Models\Company;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_generation_creates_encrypted_compensation_snapshot_and_prorates_basic(): void
    {
        [$run, $user, $employment] = $this->payrollContext(joiningDate: '2026-07-16');

        app(GeneratePayrollEntriesAction::class)->handle($run, $user);
        $entry = $run->entries()->sole();
        $rawBasic = DB::table((new PayrollEntry)->getTable())->where('id', $entry->getKey())->value('basic_salary');

        $this->assertSame('31', (string) $entry->period_days);
        $this->assertSame('16.00', $entry->payable_days);
        $this->assertEqualsWithDelta(51612.90, (float) $entry->payable_basic, 0.01);
        $this->assertSame($employment->employee->full_name, $entry->employee_name);
        $this->assertNotSame('100000', $rawBasic);
        $this->assertEqualsWithDelta(76612.90, (float) $entry->net_salary, 0.01);
    }

    public function test_generation_is_atomic_when_approved_compensation_is_missing(): void
    {
        $company = Company::factory()->create();
        Employment::factory()->forCompany($company)->create();
        $run = PayrollRun::factory()->create([
            'company_id' => $company->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ]);
        $user = User::factory()->create();
        $this->authenticate($user, $company, ['GenerateEntries:PayrollRun']);

        try {
            app(GeneratePayrollEntriesAction::class)->handle($run, $user);
            $this->fail('Missing compensation should stop generation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payroll_run', $exception->errors());
        }

        $this->assertSame(0, $run->entries()->count());
    }

    public function test_entry_recalculation_and_payment_allocation_are_validated_before_submission(): void
    {
        [$run, $user] = $this->payrollContext();
        app(GeneratePayrollEntriesAction::class)->handle($run, $user);
        $entry = $run->entries()->sole();

        $entry->update([
            'absence_deduction' => 5000,
            'loan_advance_deduction' => 10000,
            'bank_amount' => 100000,
            'cash_amount' => 0,
        ]);

        $this->assertSame(110000.0, (float) $entry->refresh()->net_salary);

        try {
            app(SubmitPayrollRunAction::class)->handle($run, $user);
            $this->fail('Unbalanced payment allocation should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payroll_run', $exception->errors());
        }

        $entry->update(['bank_amount' => 100000, 'cash_amount' => 10000]);
        app(SubmitPayrollRunAction::class)->handle($run, $user);

        $this->assertSame(PayrollRunStatus::UnderReview, $run->refresh()->status);
    }

    public function test_payroll_can_be_approved_and_submitted_content_is_immutable(): void
    {
        [$run, $user] = $this->payrollContext();
        app(GeneratePayrollEntriesAction::class)->handle($run, $user);
        app(SubmitPayrollRunAction::class)->handle($run, $user);
        app(ApprovePayrollRunAction::class)->handle($run, $user);

        $this->assertSame(PayrollRunStatus::Approved, $run->refresh()->status);
        $this->assertNotNull($run->approved_at);

        $this->expectException(ValidationException::class);
        $run->update(['notes' => 'Cannot change']);
    }

    public function test_entries_cannot_change_after_submission(): void
    {
        [$run, $user] = $this->payrollContext();
        app(GeneratePayrollEntriesAction::class)->handle($run, $user);
        app(SubmitPayrollRunAction::class)->handle($run, $user);

        $this->expectException(ValidationException::class);
        $run->entries()->sole()->update(['remarks' => 'Late change']);
    }

    /**
     * @return array{PayrollRun, User, Employment}
     */
    private function payrollContext(string $joiningDate = '2026-01-01'): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => $joiningDate,
            'employment_category' => EmploymentCategory::AdministrativeStaff,
        ]);
        EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'status' => CompensationStatus::Approved,
            'effective_from' => $joiningDate,
            'basic_salary' => 100000,
            'house_travel_allowance' => 15000,
            'food_allowance' => 10000,
            'other_allowance' => 0,
            'approved_at' => now(),
        ]);
        $run = PayrollRun::factory()->create([
            'company_id' => $company->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ]);
        $user = User::factory()->create();
        $this->authenticate($user, $company, [
            'GenerateEntries:PayrollRun', 'Submit:PayrollRun', 'Approve:PayrollRun',
            'MarkPaid:PayrollRun', 'Lock:PayrollRun',
        ]);

        return [$run, $user, $employment];
    }

    /** @param array<int, string> $permissions */
    private function authenticate(User $user, Company $company, array $permissions): void
    {
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
