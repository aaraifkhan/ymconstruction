<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryStatus;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveTreasuryTransactionAction
{
    public function handle(TreasuryTransaction $transaction, User $actor): TreasuryTransaction
    {
        Gate::forUser($actor)->authorize('approve', $transaction);

        return DB::transaction(function () use ($actor, $transaction): TreasuryTransaction {
            $transaction = TreasuryTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status !== TreasuryStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Only a submitted treasury transaction may be approved.']);
            }
            if ((int) $transaction->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['approved_by_id' => 'The preparer cannot approve the same treasury transaction.']);
            }

            $transaction->update([
                'status' => TreasuryStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            activity('treasury_transactions')->causedBy($actor)->performedOn($transaction)->event('approved')
                ->withProperties(['company_id' => $transaction->company_id, 'amount' => $transaction->amount])
                ->log('approved treasury transaction');

            return $transaction->refresh();
        }, attempts: 3);
    }
}
