<?php

namespace App\Actions\Banking;

use App\Enums\BankReconciliationStatus;
use App\Models\BankReconciliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReopenBankReconciliationAction
{
    public function handle(BankReconciliation $reconciliation, User $actor, string $reason): BankReconciliation
    {
        Gate::forUser($actor)->authorize('reopen', $reconciliation);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reopen reason is required.']);
        }

        return DB::transaction(function () use ($actor, $reason, $reconciliation): BankReconciliation {
            $reconciliation = BankReconciliation::query()->whereKey($reconciliation)->lockForUpdate()->firstOrFail();
            if ($reconciliation->status !== BankReconciliationStatus::Closed) {
                throw ValidationException::withMessages(['status' => 'Only a closed reconciliation may be reopened.']);
            }

            $reconciliation->update([
                'status' => BankReconciliationStatus::Reopened,
                'reopened_by_id' => $actor->getKey(),
                'reopened_at' => now(),
                'reopen_reason' => $reason,
            ]);
            activity('bank_reconciliations')->causedBy($actor)->performedOn($reconciliation)->event('reopened')
                ->withProperties(['company_id' => $reconciliation->company_id, 'reason' => $reason])
                ->log('reopened bank reconciliation');

            return $reconciliation->refresh();
        }, attempts: 3);
    }
}
