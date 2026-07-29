<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelEmployeeFinancingAction
{
    public function handle(EmployeeFinancing $financing, string $reason, User $actor): EmployeeFinancing
    {
        Gate::forUser($actor)->authorize('cancel', $financing);

        return DB::transaction(function () use ($financing, $reason, $actor): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if (! in_array($financing->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected, EmployeeFinancingStatus::Approved], true)
                || trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Only undisbursed financing may be cancelled with a reason.']);
            }
            EmployeeFinancingInstallment::query()->where('employee_financing_id', $financing->getKey())->update([
                'status' => EmployeeFinancingInstallmentStatus::Superseded->value,
                'updated_at' => now(),
            ]);
            $financing->update([
                'status' => EmployeeFinancingStatus::Cancelled,
                'cancelled_by_id' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
            ]);
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event('cancelled')
                ->withProperties(['company_id' => $financing->company_id, 'reason' => trim($reason)])
                ->log('cancelled employee financing');

            return $financing->refresh();
        }, 3);
    }
}
