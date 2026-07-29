<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\HR\ApproveEmployeeFinancingAction;
use App\Actions\HR\CreateEmployeeFinancingDisbursementAction;
use App\Actions\HR\CreateEmployeeFinancingRecoveryAction;
use App\Actions\HR\RescheduleEmployeeFinancingAction;
use App\Actions\HR\SubmitEmployeeFinancingAction;
use App\Actions\HR\WaiveEmployeeFinancingAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\ReverseTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use App\Enums\TreasuryStatus;
use App\Filament\Resources\EmployeeFinancings\Pages\ListEmployeeFinancings;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingTransaction;
use App\Models\Employment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeFinancingWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_request_approval_uses_maker_checker_and_builds_reconciled_schedule(): void
    {
        [$company, $employment, $maker, $approver] = $this->context();
        $financing = $this->financing($company, $employment, $maker, [
            'type' => EmployeeFinancingType::Loan,
            'principal_amount' => '10000.0000',
            'finance_charge' => '200.0000',
            'total_repayable' => '10200.0000',
            'installment_count' => 3,
        ]);

        app(SubmitEmployeeFinancingAction::class)->handle($financing, $maker);
        $this->expectValidationException(fn () => app(ApproveEmployeeFinancingAction::class)->handle($financing->refresh(), $maker));
        app(ApproveEmployeeFinancingAction::class)->handle($financing->refresh(), $approver);

        $financing->refresh();
        $this->assertSame(EmployeeFinancingStatus::Approved, $financing->status);
        $this->assertSame('LOAN-'.sprintf('%06d', $financing->getKey()), $financing->reference_number);
        $this->assertCount(3, $financing->installments);
        $this->assertSame('10200', (string) $financing->installments()->sum('total_due'));
        $this->assertSame('10000', (string) $financing->installments()->sum('principal_due'));
        $this->assertSame('3399.9999', $financing->dueAmountOnOrBefore(CarbonImmutable::parse('2026-08-31')));
        $this->assertSame('6799.9998', $financing->dueAmountOnOrBefore(CarbonImmutable::parse('2026-09-30')));
    }

    public function test_advance_disbursement_and_recovery_reconcile_to_treasury_and_gl(): void
    {
        [$company, $employment, $maker, $approver, $poster] = $this->accountingContext();
        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $bankAccount = $bank->accountingMapping()->firstOrFail()->account;
        $financing = $this->approvedFinancing($company, $employment, $maker, $approver);

        $disbursement = app(CreateEmployeeFinancingDisbursementAction::class)->handle(
            $financing,
            $bankAccount,
            $bank,
            CarbonImmutable::parse('2026-07-20'),
            $maker,
        );
        app(SubmitTreasuryTransactionAction::class)->handle($disbursement, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($disbursement, $approver);
        app(PostTreasuryTransactionAction::class)->handle($disbursement, $poster);
        app(PostTreasuryTransactionAction::class)->handle($disbursement->refresh(), $poster);

        $financing->refresh();
        $advanceAccountId = $company->accountingMappings()
            ->where('system_key', AccountingMappingKey::EmployeeAdvances)->value('account_id');
        $this->assertSame(EmployeeFinancingStatus::Active, $financing->status);
        $this->assertSame(1, $financing->transactions()->where('type', 'disbursement')->count());
        $this->assertSame('12000.0000', $disbursement->refresh()->journalEntry->lines()->where('account_id', $advanceAccountId)->sole()->debit);

        $recovery = app(CreateEmployeeFinancingRecoveryAction::class)->handle(
            $financing,
            '4000.0000',
            $bankAccount,
            $bank,
            CarbonImmutable::parse('2026-08-01'),
            $maker,
        );
        app(SubmitTreasuryTransactionAction::class)->handle($recovery, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($recovery, $approver);
        app(PostTreasuryTransactionAction::class)->handle($recovery, $poster);

        $this->assertSame('8000.0000', $financing->refresh()->outstandingAmount());
        $this->assertSame('4000.0000', $recovery->refresh()->journalEntry->lines()->where('account_id', $advanceAccountId)->sole()->credit);
        $this->assertSame('paid', $financing->installments()->orderBy('due_date')->firstOrFail()->status->value);
    }

    public function test_early_payoff_reschedule_and_treasury_reversal_preserve_history(): void
    {
        [$company, $employment, $maker, $approver, $poster] = $this->accountingContext();
        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $bankAccount = $bank->accountingMapping()->firstOrFail()->account;
        $financing = $this->approvedFinancing($company, $employment, $maker, $approver);
        $disbursement = app(CreateEmployeeFinancingDisbursementAction::class)->handle(
            $financing, $bankAccount, $bank, CarbonImmutable::parse('2026-07-20'), $maker,
        );
        $this->postTreasury($disbursement, $maker, $approver, $poster);

        app(RescheduleEmployeeFinancingAction::class)->handle(
            $financing->refresh(), 2, CarbonImmutable::parse('2026-09-01'), 'Employee requested two revised installments.', $approver,
        );
        $this->assertSame(2, $financing->installments()->where('schedule_version', 2)->count());
        $this->assertSame(3, $financing->installments()->where('status', 'superseded')->count());

        $payoff = app(CreateEmployeeFinancingRecoveryAction::class)->handle(
            $financing->refresh(), $financing->refresh()->outstandingAmount(),
            $bankAccount, $bank, CarbonImmutable::parse('2026-08-15'), $maker,
        );
        $this->postTreasury($payoff, $maker, $approver, $poster);
        $this->assertSame(EmployeeFinancingStatus::Settled, $financing->refresh()->status);

        app(ReverseTreasuryTransactionAction::class)->handle(
            $payoff->refresh(), $poster, CarbonImmutable::parse('2026-08-16'), 'Recovery receipt reversed.',
        );
        $this->assertSame(EmployeeFinancingStatus::Active, $financing->refresh()->status);
        $this->assertSame('12000.0000', $financing->refresh()->outstandingAmount());
        $this->assertSame(2, EmployeeFinancingTransaction::query()->where('treasury_transaction_id', $payoff->getKey())->where('type', 'reversal')->count());
        $this->assertSame(2, $financing->installments()->where('schedule_version', 2)->where('status', 'pending')->count());
    }

    public function test_company_isolation_immutability_and_tenant_list_are_enforced(): void
    {
        [$company, $employment, $maker, $approver] = $this->context();
        $otherCompany = Company::factory()->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create();

        $this->expectValidationException(fn () => $this->financing($company, $otherEmployment, $maker));
        $financing = $this->approvedFinancing($company, $employment, $maker, $approver);
        $this->expectValidationException(fn () => $financing->update(['principal_amount' => '1.0000']));
        $this->expectValidationException(fn () => $financing->installments()->firstOrFail()->update(['total_due' => '1.0000']));

        $this->actingAs($maker);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
        Livewire::test(ListEmployeeFinancings::class)
            ->assertCanSeeTableRecords([$financing])
            ->assertSuccessful();
    }

    public function test_approved_principal_waiver_posts_expense_and_reduces_subledger(): void
    {
        [$company, $employment, $maker, $approver, $poster] = $this->accountingContext();
        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $bankAccount = $bank->accountingMapping()->firstOrFail()->account;
        $financing = $this->approvedFinancing($company, $employment, $maker, $approver);
        $disbursement = app(CreateEmployeeFinancingDisbursementAction::class)->handle(
            $financing, $bankAccount, $bank, CarbonImmutable::parse('2026-07-20'), $maker,
        );
        $this->postTreasury($disbursement, $maker, $approver, $poster);
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();

        app(WaiveEmployeeFinancingAction::class)->handle(
            $financing->refresh(),
            '2000.0000',
            $expense,
            CarbonImmutable::parse('2026-08-01'),
            'Approved hardship waiver.',
            $approver,
        );

        $waiver = $financing->transactions()->where('type', 'waiver')->firstOrFail();
        $this->assertSame('10000.0000', $financing->refresh()->outstandingAmount());
        $this->assertNotNull($waiver->journal_entry_id);
        $this->assertSame('2000.0000', $waiver->journalEntry->lines()->where('account_id', $expense->getKey())->sole()->debit);
    }

    /** @return array{Company, Employment, User, User, User} */
    private function context(): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $role = Role::findOrCreate('super_admin');
        [$maker, $approver, $poster] = User::factory()->count(3)->create()->each->assignRole($role)->all();

        return [$company, $employment, $maker, $approver, $poster];
    }

    /** @return array{Company, Employment, User, User, User} */
    private function accountingContext(): array
    {
        [$company, $employment, $maker, $approver, $poster] = $this->context();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );

        return [$company, $employment, $maker, $approver, $poster];
    }

    private function financing(
        Company $company,
        Employment $employment,
        User $maker,
        array $overrides = [],
    ): EmployeeFinancing {
        return EmployeeFinancing::factory()->forCompany($company)->create([
            'employment_id' => $employment->getKey(),
            'requested_by_id' => $maker->getKey(),
            'request_date' => '2026-07-15',
            'first_due_date' => '2026-08-01',
            ...$overrides,
        ]);
    }

    private function approvedFinancing(
        Company $company,
        Employment $employment,
        User $maker,
        User $approver,
    ): EmployeeFinancing {
        $financing = $this->financing($company, $employment, $maker);
        app(SubmitEmployeeFinancingAction::class)->handle($financing, $maker);
        app(ApproveEmployeeFinancingAction::class)->handle($financing->refresh(), $approver);

        return $financing->refresh();
    }

    private function postTreasury($transaction, User $maker, User $approver, User $poster): void
    {
        app(SubmitTreasuryTransactionAction::class)->handle($transaction, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($transaction, $approver);
        app(PostTreasuryTransactionAction::class)->handle($transaction, $poster);
        $this->assertSame(TreasuryStatus::Posted, $transaction->refresh()->status);
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation exception.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }
}
