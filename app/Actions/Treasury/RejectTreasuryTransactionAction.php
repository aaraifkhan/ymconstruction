<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryStatus;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectTreasuryTransactionAction
{
    public function handle(TreasuryTransaction $transaction, User $actor, string $reason): TreasuryTransaction
    {
        Gate::forUser($actor)->authorize('reject', $transaction);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($actor, $reason, $transaction): TreasuryTransaction {
            $transaction = TreasuryTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if (! in_array($transaction->status, [TreasuryStatus::Submitted, TreasuryStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Only submitted or approved treasury transactions may be rejected.']);
            }

            $transaction->update([
                'status' => TreasuryStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            activity('treasury_transactions')->causedBy($actor)->performedOn($transaction)->event('rejected')
                ->withProperties(['company_id' => $transaction->company_id, 'reason' => $reason])
                ->log('rejected treasury transaction');

            return $transaction->refresh();
        }, attempts: 3);
    }
}
