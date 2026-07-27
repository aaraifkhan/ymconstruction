<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitJournalEntryAction
{
    public function __construct(private ValidateJournalEntryAction $validator) {}

    public function handle(JournalEntry $entry, User $actor): JournalEntry
    {
        Gate::forUser($actor)->authorize('submit', $entry);

        return DB::transaction(function () use ($entry, $actor): JournalEntry {
            $entry = JournalEntry::query()->whereKey($entry)->lockForUpdate()->firstOrFail();
            if (! in_array($entry->status, [JournalStatus::Draft, JournalStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected journals may be submitted.']);
            }

            $result = $this->validator->handle($entry);
            $entry->update([
                'status' => JournalStatus::Submitted,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'debit_total' => $result['debit_total'],
                'credit_total' => $result['credit_total'],
            ]);

            activity('journal_entries')->causedBy($actor)->performedOn($entry)->event('submitted')
                ->withProperties(['company_id' => $entry->company_id, ...$result])->log('submitted journal for approval');

            return $entry->refresh();
        });
    }
}
