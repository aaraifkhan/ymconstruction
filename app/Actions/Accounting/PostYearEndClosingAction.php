<?php

namespace App\Actions\Accounting;

use App\Enums\AccountType;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Enums\YearEndClosingStatus;
use App\Models\FinancialPeriod;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Models\YearEndClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostYearEndClosingAction
{
    public function __construct(
        private PrepareYearEndClosingAction $prepare,
        private PostJournalEntryAction $postJournal,
        private CloseFinancialPeriodAction $closePeriod,
    ) {}

    public function handle(YearEndClosing $closing, User $actor): YearEndClosing
    {
        Gate::forUser($actor)->authorize('post', $closing);

        return DB::transaction(function () use ($closing, $actor): YearEndClosing {
            $closing = YearEndClosing::query()->with('financialYear.company')->whereKey($closing)->lockForUpdate()->firstOrFail();
            if ($closing->status === YearEndClosingStatus::Posted) {
                return $closing;
            }
            if ($closing->status !== YearEndClosingStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only an approved year-end closing may be posted.']);
            }
            if ((int) $closing->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The preparer cannot post the year-end closing.']);
            }
            $year = FinancialYear::query()->whereKey($closing->financial_year_id)->lockForUpdate()->firstOrFail();
            $periods = FinancialPeriod::query()->where('financial_year_id', $year->getKey())->orderBy('period_number')->lockForUpdate()->get();
            $finalPeriod = $periods->last();
            if ($periods->isEmpty() || $periods->slice(0, -1)->contains('status', FinancialPeriodStatus::Open)
                || $finalPeriod->status !== FinancialPeriodStatus::Open) {
                throw ValidationException::withMessages(['financial_year_id' => 'Prior periods must be closed or locked and the final period must be open for the closing entry.']);
            }
            $snapshot = $this->prepare->snapshot($year);
            if (! hash_equals((string) $closing->calculation_checksum, $snapshot['checksum'])) {
                throw ValidationException::withMessages(['calculation_checksum' => 'Books changed after preparation. Prepare a fresh year-end closing.']);
            }
            if ($snapshot['lines'] === []) {
                throw ValidationException::withMessages(['calculation_snapshot' => 'There are no revenue or expense balances to close.']);
            }

            $entry = JournalEntry::create([
                'company_id' => $closing->company_id,
                'financial_year_id' => $year->getKey(),
                'financial_period_id' => $finalPeriod->getKey(),
                'voucher_type' => VoucherType::Journal,
                'idempotency_key' => $closing->idempotency_key,
                'status' => JournalStatus::Draft,
                'transaction_date' => $year->ends_on,
                'reference' => "YEAR-END-{$year->name}",
                'description' => "Year-end closing for {$year->name}",
                'currency_code' => 'PKR',
                'source_type' => YearEndClosing::class,
                'source_id' => $closing->getKey(),
                'prepared_by_id' => $closing->prepared_by_id,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => $closing->approved_by_id,
                'approved_at' => $closing->approved_at,
            ]);
            $lineNumber = 1;
            foreach ($snapshot['lines'] as $line) {
                $isRevenue = $line['account_type'] === AccountType::Revenue->value;
                $isPositive = bccomp($line['natural_balance'], '0', 4) === 1;
                $amount = $isPositive ? $line['natural_balance'] : bcmul($line['natural_balance'], '-1', 4);
                $closeWithDebit = $isRevenue ? $isPositive : ! $isPositive;
                JournalLine::create([
                    'journal_entry_id' => $entry->getKey(), 'company_id' => $closing->company_id,
                    'line_number' => $lineNumber++, 'account_id' => $line['account_id'],
                    'description' => 'Close annual operating balance',
                    'debit' => $closeWithDebit ? $amount : 0,
                    'credit' => $closeWithDebit ? 0 : $amount,
                ]);
            }
            if (bccomp($snapshot['profit_or_loss'], '0', 4) !== 0) {
                JournalLine::create([
                    'journal_entry_id' => $entry->getKey(), 'company_id' => $closing->company_id,
                    'line_number' => $lineNumber, 'account_id' => $closing->retained_earnings_account_id,
                    'description' => 'Transfer annual result to retained earnings',
                    'debit' => bccomp($snapshot['profit_or_loss'], '0', 4) === -1 ? bcmul($snapshot['profit_or_loss'], '-1', 4) : 0,
                    'credit' => bccomp($snapshot['profit_or_loss'], '0', 4) === 1 ? $snapshot['profit_or_loss'] : 0,
                ]);
            }
            $entry->update(['status' => JournalStatus::Approved]);
            $entry = $this->postJournal->handle($entry, $actor);
            $this->closePeriod->handle($finalPeriod, $actor);
            $year->update(['status' => FinancialPeriodStatus::Closed]);
            $closing->update([
                'status' => YearEndClosingStatus::Posted,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'journal_entry_id' => $entry->getKey(),
            ]);
            activity('year_end_closings')->causedBy($actor)->performedOn($closing)->event('posted')
                ->withProperties(['journal_entry_id' => $entry->getKey(), 'profit_or_loss' => $snapshot['profit_or_loss']])
                ->log('posted year-end closing and closed final period');

            return $closing->refresh();
        }, attempts: 3);
    }
}
