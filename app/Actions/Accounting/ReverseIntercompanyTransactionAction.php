<?php

namespace App\Actions\Accounting;

use App\Enums\IntercompanyStatus;
use App\Models\IntercompanyTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseIntercompanyTransactionAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournal) {}

    public function handle(IntercompanyTransaction $transaction, User $actor, CarbonInterface $date, string $reason): IntercompanyTransaction
    {
        Gate::forUser($actor)->authorize('reverse', $transaction);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reversal_reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($transaction, $actor, $date, $reason): IntercompanyTransaction {
            $transaction = IntercompanyTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status === IntercompanyStatus::Reversed) {
                return $transaction;
            }
            if ($transaction->status !== IntercompanyStatus::Posted) {
                throw ValidationException::withMessages(['status' => 'Only a posted pair may be reversed.']);
            }
            $origin = $this->reverseJournal->handle(JournalEntry::findOrFail($transaction->origin_journal_entry_id), $actor, $date, $reason);
            $counterparty = $this->reverseJournal->handle(JournalEntry::findOrFail($transaction->counterparty_journal_entry_id), $actor, $date, $reason);
            $transaction->update([
                'status' => IntercompanyStatus::Reversed,
                'origin_reversal_entry_id' => $origin->getKey(),
                'counterparty_reversal_entry_id' => $counterparty->getKey(),
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);
            activity('intercompany')->causedBy($actor)->performedOn($transaction)->event('reversed')
                ->withProperties(['reason' => $reason])->log('reversed inter-company pair');

            return $transaction->refresh();
        }, attempts: 3);
    }
}
