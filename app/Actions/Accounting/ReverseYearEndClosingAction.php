<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Enums\YearEndClosingStatus;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\YearEndClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseYearEndClosingAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournal) {}

    public function handle(YearEndClosing $closing, User $actor, string $reason): YearEndClosing
    {
        Gate::forUser($actor)->authorize('reverse', $closing);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reversal_reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($closing, $actor, $reason): YearEndClosing {
            $closing = YearEndClosing::query()->with('financialYear')->whereKey($closing)->lockForUpdate()->firstOrFail();
            if ($closing->status === YearEndClosingStatus::Reversed) {
                return $closing;
            }
            if ($closing->status !== YearEndClosingStatus::Posted) {
                throw ValidationException::withMessages(['status' => 'Only a posted year-end closing may be reversed.']);
            }
            $finalPeriod = FinancialPeriod::query()->where('financial_year_id', $closing->financial_year_id)
                ->orderByDesc('period_number')->lockForUpdate()->firstOrFail();
            if ($finalPeriod->status !== FinancialPeriodStatus::Open || blank($finalPeriod->reopen_reason)) {
                throw ValidationException::withMessages(['financial_period_id' => 'An authorized user must explicitly reopen the final period with a reason first.']);
            }
            $reversal = $this->reverseJournal->handle(
                JournalEntry::findOrFail($closing->journal_entry_id),
                $actor,
                $closing->financialYear->ends_on,
                $reason,
            );
            $closing->financialYear->update(['status' => FinancialPeriodStatus::Open]);
            $closing->update([
                'status' => YearEndClosingStatus::Reversed,
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_entry_id' => $reversal->getKey(),
            ]);
            activity('year_end_closings')->causedBy($actor)->performedOn($closing)->event('reversed')
                ->withProperties(['reason' => $reason, 'reversal_entry_id' => $reversal->getKey()])
                ->log('reversed year-end closing after controlled reopen');

            return $closing->refresh();
        }, attempts: 3);
    }
}
