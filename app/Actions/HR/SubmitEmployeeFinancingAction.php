<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingStatus;
use App\Models\EmployeeFinancing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitEmployeeFinancingAction
{
    public function handle(EmployeeFinancing $financing, User $actor): EmployeeFinancing
    {
        Gate::forUser($actor)->authorize('submit', $financing);

        return DB::transaction(function () use ($financing, $actor): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if (! in_array($financing->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected financing may be submitted.']);
            }
            $financing->update([
                'reference_number' => $financing->reference_number ?? sprintf('%s-%06d', strtoupper($financing->type->value), $financing->getKey()),
                'status' => EmployeeFinancingStatus::Requested,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event('submitted')
                ->withProperties(['company_id' => $financing->company_id])->log('submitted employee financing');

            return $financing->refresh();
        }, 3);
    }
}
