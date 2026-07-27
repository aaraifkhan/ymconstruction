<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveJournalEntryAction
{
    public function handle(JournalEntry $entry, User $actor): JournalEntry
    {
        Gate::forUser($actor)->authorize('approve', $entry);

        return DB::transaction(function () use ($entry, $actor): JournalEntry {
            $entry = JournalEntry::query()->whereKey($entry)->lockForUpdate()->firstOrFail();
            if ($entry->status !== JournalStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Only submitted journals may be approved.']);
            }
            if ((int) $entry->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['approved_by_id' => 'The journal preparer cannot approve the same journal.']);
            }

            $entry->update(['status' => JournalStatus::Approved, 'approved_by_id' => $actor->getKey(), 'approved_at' => now()]);
            activity('journal_entries')->causedBy($actor)->performedOn($entry)->event('approved')
                ->withProperties(['company_id' => $entry->company_id])->log('approved journal');

            return $entry->refresh();
        });
    }
}
