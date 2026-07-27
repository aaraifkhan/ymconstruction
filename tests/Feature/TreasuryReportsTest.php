<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryTransactionType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Reports\BankBookReport;
use App\Reports\CashBookReport;
use App\Reports\TreasuryPositionReport;
use App\Reports\UnreconciledBankItemReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TreasuryReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_bank_position_and_unreconciled_reports_use_posted_bank_dimensions(): void
    {
        [$company, $bank, $bankGl, $maker, $approver, $poster] = $this->foundation();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();

        $this->postTransaction(TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'type' => TreasuryTransactionType::Receipt,
            'purpose' => TreasuryPurpose::Other,
            'source_account_id' => $income,
            'destination_account_id' => $bankGl,
            'destination_company_bank_account_id' => $bank,
            'transaction_date' => '2026-07-10',
            'amount' => '1000.0000',
            'description' => 'Bank receipt',
            'prepared_by_id' => $maker,
        ]), $maker, $approver, $poster);

        $this->postTransaction(TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'type' => TreasuryTransactionType::Transfer,
            'purpose' => TreasuryPurpose::Other,
            'source_account_id' => $bankGl,
            'source_company_bank_account_id' => $bank,
            'destination_account_id' => $cash,
            'transaction_date' => '2026-07-12',
            'amount' => '300.0000',
            'description' => 'Cash withdrawal',
            'prepared_by_id' => $maker,
        ]), $maker, $approver, $poster);

        $from = CarbonImmutable::parse('2026-07-01');
        $to = CarbonImmutable::parse('2026-07-31');
        $bankBook = app(BankBookReport::class)->forBank($company, $bank, $from, $to);
        $cashBook = app(CashBookReport::class)->forCompany($company, $from, $to);
        $position = app(TreasuryPositionReport::class)->forCompany($company, $to);
        $unreconciled = app(UnreconciledBankItemReport::class)->forBank($company, $bank, $to);

        $this->assertSame('1000', (string) $bankBook['debit_total']);
        $this->assertSame('300', (string) $bankBook['credit_total']);
        $this->assertSame('700.0000', $bankBook['closing_balance']);
        $this->assertSame('300.0000', $cashBook['closing_balance']);
        $this->assertCount(2, $position);
        $this->assertSame(
            '700.0000',
            $position->first(fn (array $row): bool => $row['mapping']->company_bank_account_id === $bank->getKey())['balance'],
        );
        $this->assertSame(
            '300.0000',
            $position->first(fn (array $row): bool => $row['mapping']->system_key === AccountingMappingKey::DefaultCash)['balance'],
        );
        $this->assertCount(0, $unreconciled['statement_items']);
        $this->assertCount(2, $unreconciled['book_items']);
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

    private function postTransaction(TreasuryTransaction $transaction, User $maker, User $approver, User $poster): void
    {
        app(SubmitTreasuryTransactionAction::class)->handle($transaction, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($transaction, $approver);
        app(PostTreasuryTransactionAction::class)->handle($transaction, $poster);
    }
}
