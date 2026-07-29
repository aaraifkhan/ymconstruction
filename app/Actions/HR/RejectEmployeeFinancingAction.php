<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingStatus;
use App\Models\EmployeeFinancing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectEmployeeFinancingAction
{
    public function handle(EmployeeFinancing $financing, User $actor, string $reason): EmployeeFinancing
    {
        Gate::forUser($actor)->authorize('reject', $financing);

        return DB::transaction(function () use ($financing, $actor, $reason): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ($financing->status !== EmployeeFinancingStatus::Requested || trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Requested financing requires a rejection reason.']);
            }
            $financing->update([
                'status' => EmployeeFinancingStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => trim($reason),
            ]);
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event('rejected')
                ->withProperties(['company_id' => $financing->company_id, 'reason' => trim($reason)])
                ->log('rejected employee financing');

            return $financing->refresh();
        }, 3);
    }
}
