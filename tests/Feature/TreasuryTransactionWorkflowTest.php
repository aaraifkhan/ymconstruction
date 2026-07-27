<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\ReverseTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\JournalStatus;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryStatus;
use App\Enums\TreasuryTransactionType;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TreasuryTransactionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_uses_maker_checker_posts_once_and_reverses_with_linked_evidence(): void
    {
        [$company, $bank, $bankGl, $maker, $approver, $poster] = $this->foundation();
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();
        $transaction = TreasuryTransaction::factory()->paymentFrom($company, $bankGl, $bank)->create([
            'purpose' => TreasuryPurpose::Other,
            'destination_account_id' => $expense,
            'transaction_date' => '2026-07-15',
            'amount' => '1250.5000',
            'description' => 'Bank-paid operating expense',
            'prepared_by_id' => $maker,
        ]);

        app(SubmitTreasuryTransactionAction::class)->handle($transaction, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($transaction, $approver);
        app(PostTreasuryTransactionAction::class)->handle($transaction, $poster);
        app(PostTreasuryTransactionAction::class)->handle($transaction->fresh(), $poster);

        $transaction->refresh();
        $this->assertSame(TreasuryStatus::Posted, $transaction->status);
        $this->assertSame('PV-2026-000001', $transaction->transaction_number);
        $this->assertSame(VoucherType::Payment, $transaction->journalEntry->voucher_type);
        $this->assertSame(JournalStatus::Posted, $transaction->journalEntry->status);
        $this->assertSame('1250.5000', $transaction->journalEntry->debit_total);
        $this->assertSame(1, $transaction->journalEntry->lines()->where('company_bank_account_id', $bank->getKey())->count());
        $this->assertSame(1, $company->journalEntries()->where('source_type', TreasuryTransaction::class)->count());

        app(ReverseTreasuryTransactionAction::class)->handle(
            $transaction,
            $poster,
            CarbonImmutable::parse('2026-07-20'),
            'Incorrect operating expense.',
        );
        $firstReversalId = $transaction->refresh()->reversal_journal_entry_id;
        app(ReverseTreasuryTransactionAction::class)->handle(
            $transaction,
            $poster,
            CarbonImmutable::parse('2026-07-20'),
            'Repeated reversal request.',
        );

        $this->assertSame(TreasuryStatus::Reversed, $transaction->refresh()->status);
        $this->assertSame($firstReversalId, $transaction->reversal_journal_entry_id);
    }

    public function test_preparer_cannot_approve_own_submitted_payment(): void
    {
        [$company, $bank, $bankGl, $maker] = $this->foundation();
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();
        $transaction = TreasuryTransaction::factory()->paymentFrom($company, $bankGl, $bank)->create([
            'purpose' => TreasuryPurpose::Other,
            'destination_account_id' => $expense,
            'transaction_date' => '2026-07-15',
            'prepared_by_id' => $maker,
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($transaction, $maker);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('preparer cannot approve');
        app(ApproveTreasuryTransactionAction::class)->handle($transaction, $maker);
    }

    public function test_same_company_bank_to_cash_transfer_posts_contra_and_cross_company_account_is_rejected(): void
    {
        [$company, $bank, $bankGl, $maker, $approver, $poster] = $this->foundation();
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $transfer = TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'type' => TreasuryTransactionType::Transfer,
            'purpose' => TreasuryPurpose::Other,
            'source_account_id' => $bankGl,
            'source_company_bank_account_id' => $bank,
            'destination_account_id' => $cash,
            'transaction_date' => '2026-07-15',
            'amount' => '5000.0000',
            'description' => 'Bank withdrawal to head-office cash',
            'prepared_by_id' => $maker,
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($transfer, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($transfer, $approver);
        app(PostTreasuryTransactionAction::class)->handle($transfer, $poster);

        $this->assertSame(VoucherType::Contra, $transfer->refresh()->journalEntry->voucher_type);
        $this->assertSame('5000', (string) $transfer->journalEntry->lines()->where('account_id', $cash->getKey())->sum('debit'));
        $this->assertSame('5000', (string) $transfer->journalEntry->lines()->where('account_id', $bankGl->getKey())->sum('credit'));

        $otherCompany = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $otherCompany,
            AccountingProfile::Generic,
            CarbonImmutable::parse('2026-07-15'),
        );

        $this->expectException(ValidationException::class);
        TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'type' => TreasuryTransactionType::Transfer,
            'purpose' => TreasuryPurpose::Other,
            'source_account_id' => $bankGl,
            'source_company_bank_account_id' => $bank,
            'destination_account_id' => $otherCompany->accounts()->where('code', '1111')->firstOrFail(),
            'transaction_date' => '2026-07-15',
            'prepared_by_id' => $maker,
        ]);
    }

    /** @return array{Company, CompanyBankAccount, Account, User, User, User} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Generic,
            CarbonImmutable::parse('2026-07-15'),
        );
        $bank = CompanyBankAccount::factory()->create(['company_id' => $company]);
        $bankGl = $bank->accountingMapping()->firstOrFail()->account;
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(3)->create();
        $users->each->assignRole($role);

        return [$company, $bank, $bankGl, ...$users->all()];
    }
}
