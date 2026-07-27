<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\AccountingProfile;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Reports\BalanceSheetReport;
use App\Reports\GeneralLedgerReport;
use App\Reports\ProfitAndLossReport;
use App\Reports\TrialBalanceReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_reconcile_exactly_to_posted_journal_lines(): void
    {
        [$company, $maker, $approver, $poster] = $this->foundation();
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $income = $company->accounts()->where('code', '4700')->firstOrFail();
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();
        $this->postJournal($company, $maker, $approver, $poster, $cash->getKey(), $income->getKey(), 100, '2026-07-10');
        $this->postJournal($company, $maker, $approver, $poster, $expense->getKey(), $cash->getKey(), 40, '2026-07-12');
        $from = CarbonImmutable::parse('2026-07-01');
        $to = CarbonImmutable::parse('2026-07-31');

        $trial = app(TrialBalanceReport::class);
        $trialRows = $trial->forCompany($company, $to);
        $trialTotals = $trial->totals($trialRows);
        $profitAndLoss = app(ProfitAndLossReport::class)->forCompany($company, $from, $to);
        $balanceSheet = app(BalanceSheetReport::class)->forCompany($company, $to);
        $ledger = app(GeneralLedgerReport::class)->forAccount($company, $cash, $from, $to);

        $this->assertSame($trialTotals['debit'], $trialTotals['credit']);
        $this->assertSame('60.0000', $profitAndLoss['profit_or_loss']);
        $this->assertTrue($balanceSheet['balances']);
        $this->assertSame('60.0000', $balanceSheet['asset_total']);
        $this->assertSame('60.0000', $ledger['closing_balance']);
        $this->assertCount(2, $ledger['lines']);
    }

    /** @return array{Company, User, User, User} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(3)->create();
        $users->each->assignRole($role);

        return [$company, ...$users->all()];
    }

    private function postJournal(Company $company, User $maker, User $approver, User $poster, int $debitAccountId, int $creditAccountId, int $amount, string $date): void
    {
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();
        $entry = JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(), 'transaction_date' => $date,
            'description' => 'Report test journal', 'prepared_by_id' => $maker->getKey(),
        ]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $debitAccountId, 'debit' => $amount]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $creditAccountId, 'credit' => $amount]);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        app(PostJournalEntryAction::class)->handle($entry, $poster);
    }
}
