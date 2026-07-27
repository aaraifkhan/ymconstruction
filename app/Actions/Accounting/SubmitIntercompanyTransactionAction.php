<?php

namespace App\Actions\Accounting;

use App\Enums\IntercompanyStatus;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitIntercompanyTransactionAction
{
    public function handle(IntercompanyTransaction $transaction, User $actor): IntercompanyTransaction
    {
        Gate::forUser($actor)->authorize('submit', $transaction);

        return DB::transaction(function () use ($transaction, $actor): IntercompanyTransaction {
            $transaction = IntercompanyTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if (! in_array($transaction->status, [IntercompanyStatus::Draft, IntercompanyStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected inter-company transactions may be submitted.']);
            }
            $transaction->update([
                'status' => IntercompanyStatus::PendingApprovals,
                'origin_approved_by_id' => null,
                'origin_approved_at' => null,
                'counterparty_approved_by_id' => null,
                'counterparty_approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            activity('intercompany')->causedBy($actor)->performedOn($transaction)->event('submitted')
                ->withProperties(['company_id' => $transaction->company_id, 'counterparty_company_id' => $transaction->counterparty_company_id])
                ->log('submitted inter-company transaction');

            return $transaction->refresh();
        });
    }
}
