<?php

namespace App\Actions\Accounting;

use App\Enums\IntercompanyStatus;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveIntercompanyTransactionAction
{
    public function handleOrigin(IntercompanyTransaction $transaction, User $actor): IntercompanyTransaction
    {
        Gate::forUser($actor)->authorize('approveOrigin', $transaction);

        return $this->approve($transaction, $actor, true);
    }

    public function handleCounterparty(IntercompanyTransaction $transaction, User $actor): IntercompanyTransaction
    {
        Gate::forUser($actor)->authorize('approveCounterparty', $transaction);

        return $this->approve($transaction, $actor, false);
    }

    private function approve(IntercompanyTransaction $transaction, User $actor, bool $origin): IntercompanyTransaction
    {
        return DB::transaction(function () use ($transaction, $actor, $origin): IntercompanyTransaction {
            $transaction = IntercompanyTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status !== IntercompanyStatus::PendingApprovals) {
                throw ValidationException::withMessages(['status' => 'Only a submitted inter-company transaction may be approved.']);
            }
            if ((int) $transaction->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The preparer cannot approve either company side.']);
            }
            $otherApproverId = $origin ? $transaction->counterparty_approved_by_id : $transaction->origin_approved_by_id;
            if ($otherApproverId !== null && (int) $otherApproverId === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'Each company side requires a different approver.']);
            }
            $transaction->update($origin
                ? ['origin_approved_by_id' => $actor->getKey(), 'origin_approved_at' => now()]
                : ['counterparty_approved_by_id' => $actor->getKey(), 'counterparty_approved_at' => now()]);
            if ($transaction->origin_approved_by_id !== null && $transaction->counterparty_approved_by_id !== null) {
                $transaction->update(['status' => IntercompanyStatus::Approved]);
            }
            activity('intercompany')->causedBy($actor)->performedOn($transaction)
                ->event($origin ? 'origin-approved' : 'counterparty-approved')
                ->withProperties(['company_id' => $origin ? $transaction->company_id : $transaction->counterparty_company_id])
                ->log('approved inter-company company side');

            return $transaction->refresh();
        });
    }
}
