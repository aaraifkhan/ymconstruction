<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Banking\CloseBankReconciliationAction;
use App\Actions\Banking\ImportBankStatementAction;
use App\Actions\Banking\MatchBankStatementLineAction;
use App\Actions\Banking\PostBankReconciliationAdjustmentAction;
use App\Actions\Banking\ReopenBankReconciliationAction;
use App\Actions\Banking\UnmatchBankStatementLineAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\BankReconciliationStatus;
use App\Enums\BankStatementStatus;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryTransactionType;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_statement_import_matches_gl_closes_and_reopens_with_audit_evidence(): void
    {
        Storage::fake('local');
        [$company, $bank, $bankGl, $maker, $approver, $poster, $importer, $closer] = $this->foundation();
        $receipt = $this->postedBankReceipt($company, $bank, $bankGl, $maker, $approver, $poster);
        $statement = BankStatement::factory()->create([
            'company_id' => $company,
            'company_bank_account_id' => $bank,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'opening_balance' => '0.0000',
            'closing_balance' => '1000.0000',
        ]);
        $path = "bank-statements/{$company->getKey()}/july.csv";
        Storage::disk('local')->put($path, implode("\n", [
            'transaction_date,value_date,description,reference,debit,credit,balance',
            '2026-07-15,2026-07-15,Client receipt,BR-001,0,1000,1000',
        ]));

        app(ImportBankStatementAction::class)->handle($statement, $path, 'july.csv', $importer);
        $statement->refresh();
        $reconciliation = $statement->reconciliation()->firstOrFail();
        $line = $statement->lines()->firstOrFail();
        $bankJournalLine = $receipt->journalEntry->lines()
            ->where('company_bank_account_id', $bank->getKey())->firstOrFail();
        $match = app(MatchBankStatementLineAction::class)->handle(
            $reconciliation,
            $line,
            $bankJournalLine,
            '1000.0000',
            $importer,
        );

        $this->assertSame(BankStatementStatus::Imported, $statement->status);
        $this->assertNotNull($statement->source_sha256);
        $this->assertSame('1000.0000', $match->amount);

        app(UnmatchBankStatementLineAction::class)->handle($match, $importer);
        $this->assertDatabaseMissing('bank_reconciliation_matches', ['id' => $match->getKey()]);
        app(MatchBankStatementLineAction::class)->handle(
            $reconciliation,
            $line,
            $bankJournalLine,
            '1000.0000',
            $importer,
        );

        app(CloseBankReconciliationAction::class)->handle($reconciliation, $closer);
        $this->assertSame(BankReconciliationStatus::Closed, $reconciliation->refresh()->status);
        $this->assertSame('0.0000', $reconciliation->difference);
        $this->assertSame(BankStatementStatus::Locked, $statement->refresh()->status);

        app(ReopenBankReconciliationAction::class)->handle($reconciliation, $closer, 'Bank supplied a revised reference.');
        $this->assertSame(BankReconciliationStatus::Reopened, $reconciliation->refresh()->status);
        $this->assertSame('Bank supplied a revised reference.', $reconciliation->reopen_reason);
    }

    public function test_import_rejects_invalid_running_balance_and_rolls_back_all_lines(): void
    {
        Storage::fake('local');
        [$company, $bank, , , , , $importer] = $this->foundation();
        $statement = BankStatement::factory()->create([
            'company_id' => $company,
            'company_bank_account_id' => $bank,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'opening_balance' => '0.0000',
            'closing_balance' => '900.0000',
        ]);
        $path = "bank-statements/{$company->getKey()}/invalid.csv";
        Storage::disk('local')->put($path, implode("\n", [
            'transaction_date,value_date,description,reference,debit,credit,balance',
            '2026-07-15,,Invalid balance,BR-X,0,1000,900',
        ]));

        try {
            app(ImportBankStatementAction::class)->handle($statement, $path, 'invalid.csv', $importer);
            $this->fail('An invalid running balance should fail.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Running balance', $exception->getMessage());
        }

        $this->assertSame(BankStatementStatus::Draft, $statement->refresh()->status);
        $this->assertSame(0, $statement->lines()->count());
        $this->assertNull($statement->reconciliation);
    }

    public function test_authorized_adjustment_posts_and_matches_unrecorded_bank_charge(): void
    {
        Storage::fake('local');
        [$company, $bank, , , , , $importer, $adjuster] = $this->foundation();
        $statement = BankStatement::factory()->create([
            'company_id' => $company,
            'company_bank_account_id' => $bank,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'opening_balance' => '0.0000',
            'closing_balance' => '-50.0000',
        ]);
        $path = "bank-statements/{$company->getKey()}/charges.csv";
        Storage::disk('local')->put($path, implode("\n", [
            'transaction_date,value_date,description,reference,debit,credit,balance',
            '2026-07-16,2026-07-16,Bank charges,FEE-001,50,0,-50',
        ]));
        app(ImportBankStatementAction::class)->handle($statement, $path, 'charges.csv', $importer);
        $reconciliation = $statement->refresh()->reconciliation()->firstOrFail();
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();

        $match = app(PostBankReconciliationAdjustmentAction::class)->handle(
            $reconciliation,
            $statement->lines()->firstOrFail(),
            $expense,
            'Monthly bank service charge.',
            $adjuster,
        );

        $this->assertSame('50.0000', $match->amount);
        $this->assertSame($bank->getKey(), $match->journalLine->company_bank_account_id);
        $this->assertSame('50.0000', $match->journalLine->credit);
        $this->assertSame(
            $match->journalLine->journalEntry->debit_total,
            $match->journalLine->journalEntry->credit_total,
        );
        app(CloseBankReconciliationAction::class)->handle($reconciliation, $adjuster);
        $this->assertSame(BankReconciliationStatus::Closed, $reconciliation->refresh()->status);
    }

    /** @return array{Company, CompanyBankAccount, Account, User, User, User, User, User} */
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
        $users = User::factory()->count(5)->create();
        $users->each->assignRole($role);

        return [$company, $bank, $bankGl, ...$users->all()];
    }

    private function postedBankReceipt(
        Company $company,
        CompanyBankAccount $bank,
        Account $bankGl,
        User $maker,
        User $approver,
        User $poster,
    ): TreasuryTransaction {
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        $transaction = TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'type' => TreasuryTransactionType::Receipt,
            'purpose' => TreasuryPurpose::Other,
            'source_account_id' => $income,
            'destination_account_id' => $bankGl,
            'destination_company_bank_account_id' => $bank,
            'transaction_date' => '2026-07-15',
            'amount' => '1000.0000',
            'description' => 'Client receipt',
            'prepared_by_id' => $maker,
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($transaction, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($transaction, $approver);
        app(PostTreasuryTransactionAction::class)->handle($transaction, $poster);

        return $transaction->refresh();
    }
}
