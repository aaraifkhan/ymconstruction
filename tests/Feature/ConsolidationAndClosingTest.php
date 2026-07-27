<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveIntercompanyTransactionAction;
use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\ApproveYearEndClosingAction;
use App\Actions\Accounting\CloseFinancialPeriodAction;
use App\Actions\Accounting\PostIntercompanyTransactionAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\PostYearEndClosingAction;
use App\Actions\Accounting\PrepareYearEndClosingAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\ReopenFinancialPeriodAction;
use App\Actions\Accounting\ReverseYearEndClosingAction;
use App\Actions\Accounting\SubmitIntercompanyTransactionAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\AccountingProfile;
use App\Enums\IntercompanyDirection;
use App\Enums\VoucherType;
use App\Enums\YearEndClosingStatus;
use App\Models\Company;
use App\Models\IntercompanyTransaction;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Reports\AccountingRecoveryManifest;
use App\Reports\ConsolidatedFinancialReport;
use App\Reports\FinancialReportCsvExporter;
use App\Reports\ProfitAndLossReport;
use App\Reports\TrialBalanceReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsolidationAndClosingTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_report_maps_company_books_and_eliminates_internal_controls(): void
    {
        [$root, $child, $maker, $originApprover, $counterpartyApprover, $poster] = $this->foundation(true);
        $transaction = IntercompanyTransaction::create([
            'company_id' => $root->getKey(), 'counterparty_company_id' => $child->getKey(),
            'idempotency_key' => Str::uuid(), 'transaction_date' => '2026-07-15',
            'direction' => IntercompanyDirection::OriginReceivable, 'amount' => 500,
            'origin_offset_account_id' => $root->accounts()->where('code', '1111')->value('id'),
            'counterparty_offset_account_id' => $child->accounts()->where('code', '6900')->value('id'),
            'description' => 'Group recharge', 'prepared_by_id' => $maker->getKey(),
        ]);
        app(SubmitIntercompanyTransactionAction::class)->handle($transaction, $maker);
        app(ApproveIntercompanyTransactionAction::class)->handleOrigin($transaction, $originApprover);
        app(ApproveIntercompanyTransactionAction::class)->handleCounterparty($transaction, $counterpartyApprover);
        app(PostIntercompanyTransactionAction::class)->handle($transaction, $poster);

        $report = app(ConsolidatedFinancialReport::class)->forGroup($poster, $root, CarbonImmutable::parse('2026-07-31'));
        $this->assertCount(2, $report['companies']);
        $this->assertTrue($report['reconciles']);
        $this->assertSame($report['trial_balance_totals']['debit'], $report['trial_balance_totals']['credit']);
        $this->assertTrue($report['intercompany_reconciliation']->first()['reconciles']);
        $csv = app(FinancialReportCsvExporter::class)->export($report['trial_balance'], [
            'code' => 'Code', 'name' => 'Account', 'debit_balance' => 'Debit', 'credit_balance' => 'Credit',
        ]);
        $this->assertStringContainsString('Code,Account,Debit,Credit', $csv);
        $manifest = app(AccountingRecoveryManifest::class)->generate($root);
        $this->assertTrue(app(AccountingRecoveryManifest::class)->verify($root, $manifest)['matches']);
    }

    public function test_year_end_closing_is_reproducible_closes_profit_and_requires_reopen_to_reverse(): void
    {
        [$company, , $maker, $approver, $poster] = $this->foundation(false);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $revenue = $company->accounts()->where('code', '4700')->firstOrFail();
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();
        $this->postJournal($company, $maker, $approver, $poster, $cash->getKey(), $revenue->getKey(), 100);
        $this->postJournal($company, $maker, $approver, $poster, $expense->getKey(), $cash->getKey(), 40);
        $year = $company->financialYears()->firstOrFail();
        foreach ($year->periods()->where('period_number', '<', 12)->get() as $period) {
            app(CloseFinancialPeriodAction::class)->handle($period, $poster);
        }

        $closing = app(PrepareYearEndClosingAction::class)->handle($year, $maker);
        $this->assertSame('60.0000', $closing->profit_or_loss);
        app(ApproveYearEndClosingAction::class)->handle($closing, $approver);
        $posted = app(PostYearEndClosingAction::class)->handle($closing, $poster);

        $this->assertSame(YearEndClosingStatus::Posted, $posted->status);
        $this->assertSame('0.0000', app(ProfitAndLossReport::class)->forCompany(
            $company,
            $year->starts_on,
            $year->ends_on,
        )['profit_or_loss']);
        $retained = app(TrialBalanceReport::class)->forCompany($company, $year->ends_on)->firstWhere('code', '3200');
        $this->assertSame('60.0000', $retained['natural_balance']);

        $finalPeriod = $year->periods()->where('period_number', 12)->firstOrFail();
        app(ReopenFinancialPeriodAction::class)->handle($finalPeriod, $poster, 'Approved audit adjustment');
        $reversed = app(ReverseYearEndClosingAction::class)->handle($posted, $poster, 'Reopen annual result');
        $this->assertSame(YearEndClosingStatus::Reversed, $reversed->status);
        $this->assertSame('60.0000', app(ProfitAndLossReport::class)->forCompany(
            $company,
            $year->starts_on,
            $year->ends_on,
        )['profit_or_loss']);
    }

    /** @return array{Company, Company, User, User, User, User} */
    private function foundation(bool $withChild): array
    {
        $root = Company::factory()->create(['name' => '7-Orbit']);
        $child = Company::factory()->create([
            'name' => '7-Orbit IT',
            'parent_company_id' => $withChild ? $root->getKey() : null,
        ]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        foreach ([$root, $child] as $company) {
            app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        }
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(4)->create();
        $users->each->assignRole($role);

        return [$root, $child, ...$users->all()];
    }

    private function postJournal(Company $company, User $maker, User $approver, User $poster, int $debit, int $credit, int $amount): void
    {
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();
        $entry = JournalEntry::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal,
            'idempotency_key' => Str::uuid(), 'transaction_date' => '2026-07-15',
            'description' => 'Year close source', 'prepared_by_id' => $maker->getKey(),
        ]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $debit, 'debit' => $amount]);
        JournalLine::create(['journal_entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $credit, 'credit' => $amount]);
        app(SubmitJournalEntryAction::class)->handle($entry, $maker);
        app(ApproveJournalEntryAction::class)->handle($entry, $approver);
        app(PostJournalEntryAction::class)->handle($entry, $poster);
    }
}
