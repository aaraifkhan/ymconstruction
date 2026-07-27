<?php

namespace App\Actions\Accounting;

use App\Enums\IntercompanyStatus;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectIntercompanyTransactionAction
{
    public function handle(IntercompanyTransaction $transaction, User $actor, string $reason): IntercompanyTransaction
    {
        Gate::forUser($actor)->authorize('reject', $transaction);
        if (blank($reason)) {
            throw ValidationException::withMessages(['rejection_reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($transaction, $actor, $reason): IntercompanyTransaction {
            $transaction = IntercompanyTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if (! in_array($transaction->status, [IntercompanyStatus::PendingApprovals, IntercompanyStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Only a transaction awaiting posting may be rejected.']);
            }
            $transaction->update([
                'status' => IntercompanyStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            activity('intercompany')->causedBy($actor)->performedOn($transaction)->event('rejected')
                ->withProperties(['reason' => $reason])->log('rejected inter-company transaction');

            return $transaction->refresh();
        });
    }
}
