<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReverseJournalEntryAction
{
    public function __construct(private PostJournalEntryAction $postJournal) {}

    public function handle(JournalEntry $entry, User $actor, CarbonInterface $reversalDate, string $reason): JournalEntry
    {
        Gate::forUser($actor)->authorize('reverse', $entry);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($entry, $actor, $reversalDate, $reason): JournalEntry {
            $original = JournalEntry::query()->with('lines')->whereKey($entry)->lockForUpdate()->firstOrFail();
            if ($original->reversed_by_entry_id !== null) {
                return JournalEntry::query()->findOrFail($original->reversed_by_entry_id);
            }
            if ($original->status !== JournalStatus::Posted) {
                throw ValidationException::withMessages(['status' => 'Only posted journals may be reversed.']);
            }
            if ((int) $original->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The original preparer cannot post its reversal.']);
            }

            $period = FinancialPeriod::query()
                ->where('company_id', $original->company_id)
                ->whereDate('starts_on', '<=', $reversalDate)
                ->whereDate('ends_on', '>=', $reversalDate)
                ->where('status', FinancialPeriodStatus::Open)
                ->lockForUpdate()
                ->first();
            if ($period === null) {
                throw ValidationException::withMessages(['reversal_date' => 'Reversal date must belong to an open company period.']);
            }

            $reversal = JournalEntry::create([
                'company_id' => $original->company_id,
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => VoucherType::Reversal,
                'idempotency_key' => Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $reversalDate,
                'reference' => $original->voucher_number,
                'description' => "Reversal of {$original->voucher_number}: {$reason}",
                'currency_code' => $original->currency_code,
                'prepared_by_id' => $original->prepared_by_id,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
                'reverses_entry_id' => $original->getKey(),
            ]);

            foreach ($original->lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $reversal->getKey(), 'company_id' => $reversal->company_id,
                    'related_company_id' => $line->related_company_id,
                    'line_number' => $line->line_number, 'account_id' => $line->account_id,
                    'description' => $line->description, 'debit' => $line->credit, 'credit' => $line->debit,
                    'party_id' => $line->party_id, 'project_id' => $line->project_id,
                    'project_site_id' => $line->project_site_id, 'cost_center_id' => $line->cost_center_id,
                    'employment_id' => $line->employment_id, 'company_bank_account_id' => $line->company_bank_account_id,
                    'fixed_asset_id' => $line->fixed_asset_id,
                ]);
            }

            $reversal->update(['status' => JournalStatus::Approved]);
            $reversal = $this->postJournal->handle($reversal, $actor);
            $original->update(['status' => JournalStatus::Reversed, 'reversed_by_entry_id' => $reversal->getKey()]);
            activity('journal_entries')->causedBy($actor)->performedOn($original)->event('reversed')
                ->withProperties(['company_id' => $original->company_id, 'reversal_entry_id' => $reversal->getKey(), 'reason' => $reason])
                ->log('reversed posted journal');

            return $reversal;
        }, attempts: 3);
    }
}
