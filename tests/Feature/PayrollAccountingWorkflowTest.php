<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\GeneratePayrollEntriesAction;
use App\Actions\Payroll\LockPayrollRunAction;
use App\Actions\Payroll\MarkPayrollRunPaidAction;
use App\Actions\Payroll\PostPayrollRunAction;
use App\Actions\Payroll\ReversePayrollRunAction;
use App\Actions\Payroll\SubmitPayrollRunAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\CompensationStatus;
use App\Enums\EmploymentCategory;
use App\Enums\JournalStatus;
use App\Enums\PayrollAccountComponent;
use App\Enums\PayrollRunStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryPurpose;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\PayrollAccountMapping;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Reports\EmployeeAdvanceLedgerReport;
use App\Reports\PayrollReconciliationReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollAccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_posts_once_settles_through_treasury_and_locks(): void
    {
        [$company, $run, $employment, $maker, $approver, $poster] = $this->approvedPayroll(submit: false);
        $entry = $run->entries()->sole();
        $entry->update(['loan_advance_deduction' => 1000, 'bank_amount' => 124000]);
        app(SubmitPayrollRunAction::class)->handle($run, $maker);
        app(ApprovePayrollRunAction::class)->handle($run, $approver);
        $this->configureExpenseMappings($company);

        app(PostPayrollRunAction::class)->handle($run, $poster);
        app(PostPayrollRunAction::class)->handle($run->fresh(), $poster);

        $run->refresh();
        $this->assertNotNull($run->journal_entry_id);
        $this->assertSame(1, $company->journalEntries()->where('source_type', PayrollRun::class)->count());
        $this->assertSame('125000.0000', $run->journalEntry->debit_total);
        $this->assertSame('1000', (string) $run->journalEntry->lines()
            ->where('account_id', $company->accountingMappings()->where('system_key', 'employee_advances')->value('account_id'))
            ->sum('credit'));

        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $payment = TreasuryTransaction::factory()->paymentFrom(
            $company,
            $bank->accountingMapping()->firstOrFail()->account,
            $bank,
        )->create([
            'purpose' => TreasuryPurpose::Settlement,
            'counterparty_type' => TreasuryCounterpartyType::Employment,
            'employment_id' => $employment->getKey(),
            'party_id' => null,
            'amount' => '124000.0000',
            'transaction_date' => '2026-07-31',
            'prepared_by_id' => $maker,
        ]);
        $payment->allocations()->create([
            'company_id' => $company->getKey(),
            'allocatable_type' => $entry::class,
            'allocatable_id' => $entry->getKey(),
            'allocation_type' => TreasuryAllocationType::PayrollEntry,
            'amount' => '124000.0000',
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($payment, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($payment, $approver);
        app(PostTreasuryTransactionAction::class)->handle($payment, $poster);

        $this->assertSame('0.0000', $entry->fresh()->postedOpenAmount());
        app(MarkPayrollRunPaidAction::class)->handle($run->fresh(), $poster);
        app(LockPayrollRunAction::class)->handle($run->fresh(), $poster);
        $this->assertSame(PayrollRunStatus::Locked, $run->fresh()->status);

        $reconciliation = app(PayrollReconciliationReport::class)->forCompany($company)->sole();
        $this->assertTrue($reconciliation['reconciled']);
        $this->assertSame('124000.0000', $reconciliation['settled']);
        $this->assertSame('0.0000', $reconciliation['open']);
        $this->assertEqualsWithDelta(1000, (float) app(EmployeeAdvanceLedgerReport::class)
            ->forCompany($company)->sum('credit'), 0.0001);
    }

    public function test_project_staff_allocation_must_reconcile_and_posting_can_be_reversed(): void
    {
        [$company, $run, , $maker, $approver, $poster] = $this->approvedPayroll(EmploymentCategory::ProjectStaff, submit: false);
        $entry = $run->entries()->sole();
        $project = Project::factory()->create(['company_id' => $company->getKey()]);
        $labour = $company->accounts()->where('code', '7190')->firstOrFail();

        try {
            app(SubmitPayrollRunAction::class)->handle($run, $maker);
            $this->fail('Missing project allocation should stop submission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payroll_run', $exception->errors());
        }

        $entry->projectAllocations()->create([
            'company_id' => $company->getKey(),
            'project_id' => $project->getKey(),
            'expense_account_id' => $labour->getKey(),
            'amount' => $entry->expenseBasis(),
        ]);
        app(SubmitPayrollRunAction::class)->handle($run, $maker);
        app(ApprovePayrollRunAction::class)->handle($run, $approver);
        app(PostPayrollRunAction::class)->handle($run, $poster);

        $projectLine = $run->fresh()->journalEntry->lines()->where('project_id', $project->getKey())->sole();
        $this->assertSame($labour->getKey(), $projectLine->account_id);
        $this->assertSame('125000.0000', $projectLine->debit);

        app(ReversePayrollRunAction::class)->handle(
            $run->fresh(),
            $poster,
            CarbonImmutable::parse('2026-07-31'),
            'Project payroll allocation correction.',
        );
        $this->assertNotNull($run->fresh()->reversal_journal_entry_id);
        $this->assertSame(JournalStatus::Reversed, $run->fresh()->journalEntry->status);

        $originalJournalId = $run->fresh()->journal_entry_id;
        app(PostPayrollRunAction::class)->handle($run->fresh(), $poster);
        $this->assertNotSame($originalJournalId, $run->fresh()->journal_entry_id);
        $this->assertSame(2, $company->journalEntries()->where('source_type', PayrollRun::class)->count());
        $this->assertTrue($run->fresh()->isPostedToAccounts());
    }

    /** @return array{Company, PayrollRun, Employment, User, User, User} */
    private function approvedPayroll(
        EmploymentCategory $category = EmploymentCategory::AdministrativeStaff,
        bool $submit = true,
    ): array {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $role = Role::findOrCreate('super_admin');
        [$maker, $approver, $poster] = User::factory()->count(3)->create()->each->assignRole($role)->all();
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
            'employment_category' => $category,
        ]);
        EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'status' => CompensationStatus::Approved,
            'effective_from' => '2026-01-01',
            'basic_salary' => 100000,
            'house_travel_allowance' => 15000,
            'food_allowance' => 10000,
            'other_allowance' => 0,
            'approved_at' => now(),
        ]);
        $run = PayrollRun::factory()->create([
            'company_id' => $company->getKey(),
            'created_by_id' => $maker->getKey(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ]);
        app(GeneratePayrollEntriesAction::class)->handle($run, $maker);
        if ($submit) {
            app(SubmitPayrollRunAction::class)->handle($run, $maker);
            app(ApprovePayrollRunAction::class)->handle($run, $approver);
        }

        return [$company, $run, $employment, $maker, $approver, $poster];
    }

    private function configureExpenseMappings(Company $company): void
    {
        $salary = $company->accounts()->where('code', '5100')->firstOrFail();
        foreach ([
            PayrollAccountComponent::BasicSalary,
            PayrollAccountComponent::HouseTravelAllowance,
            PayrollAccountComponent::FoodAllowance,
        ] as $component) {
            PayrollAccountMapping::query()->create([
                'company_id' => $company->getKey(),
                'component' => $component,
                'account_id' => $salary->getKey(),
                'is_active' => true,
            ]);
        }
    }
}
