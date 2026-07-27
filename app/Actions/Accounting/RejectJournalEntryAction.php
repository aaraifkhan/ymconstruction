<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectJournalEntryAction
{
    public function handle(JournalEntry $entry, User $actor, string $reason): JournalEntry
    {
        Gate::forUser($actor)->authorize('reject', $entry);
        if (blank($reason)) {
            throw ValidationException::withMessages(['rejection_reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($entry, $actor, $reason): JournalEntry {
            $entry = JournalEntry::query()->whereKey($entry)->lockForUpdate()->firstOrFail();
            if (! in_array($entry->status, [JournalStatus::Submitted, JournalStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Only submitted or approved journals may be rejected.']);
            }

            $entry->update([
                'status' => JournalStatus::Rejected, 'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(), 'rejection_reason' => $reason,
                'approved_by_id' => null, 'approved_at' => null,
            ]);
            activity('journal_entries')->causedBy($actor)->performedOn($entry)->event('rejected')
                ->withProperties(['company_id' => $entry->company_id, 'reason' => $reason])->log('rejected journal');

            return $entry->refresh();
        });
    }
}
