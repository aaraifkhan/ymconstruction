<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VoucherSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostJournalEntryAction
{
    public function __construct(
        private ValidateJournalEntryAction $validator,
        private ReserveVoucherNumberAction $reserveVoucherNumber,
    ) {}

    public function handle(JournalEntry $entry, User $actor): JournalEntry
    {
        Gate::forUser($actor)->authorize('post', $entry);

        return DB::transaction(function () use ($entry, $actor): JournalEntry {
            $entry = JournalEntry::query()->whereKey($entry)->lockForUpdate()->firstOrFail();
            if ($entry->status === JournalStatus::Posted) {
                return $entry;
            }
            if ($entry->status !== JournalStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only approved journals may be posted.']);
            }
            if ((int) $entry->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['posted_by_id' => 'The journal preparer cannot post the same journal.']);
            }

            FinancialPeriod::query()->whereKey($entry->financial_period_id)->lockForUpdate()->firstOrFail();
            $result = $this->validator->handle($entry->fresh());
            $sequence = VoucherSequence::query()
                ->where('company_id', $entry->company_id)
                ->where('financial_year_id', $entry->financial_year_id)
                ->where('voucher_type', $entry->voucher_type)
                ->firstOrFail();

            $entry->update([
                'voucher_number' => $this->reserveVoucherNumber->handle($sequence),
                'status' => JournalStatus::Posted,
                'debit_total' => $result['debit_total'],
                'credit_total' => $result['credit_total'],
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
            ]);

            activity('journal_entries')->causedBy($actor)->performedOn($entry)->event('posted')
                ->withProperties(['company_id' => $entry->company_id, 'voucher_number' => $entry->voucher_number, ...$result])
                ->log('posted journal');

            return $entry->refresh();
        }, attempts: 3);
    }
}
