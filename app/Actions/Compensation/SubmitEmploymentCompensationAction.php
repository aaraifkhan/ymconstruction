<?php

namespace App\Actions\Compensation;

use App\Enums\CompensationStatus;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitEmploymentCompensationAction
{
    public function handle(EmploymentCompensation $compensation, User $actor): EmploymentCompensation
    {
        return DB::transaction(function () use ($actor, $compensation): EmploymentCompensation {
            $lockedCompensation = EmploymentCompensation::query()
                ->whereKey($compensation)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('submit', $lockedCompensation);

            if (! in_array($lockedCompensation->status, [CompensationStatus::Draft, CompensationStatus::Rejected], true)) {
                throw ValidationException::withMessages([
                    'employment_compensation' => 'Only draft or rejected compensation can be submitted.',
                ]);
            }

            $lockedCompensation->update([
                'status' => CompensationStatus::PendingApproval,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('employment_compensations')
                ->causedBy($actor)
                ->performedOn($lockedCompensation)
                ->event('submitted')
                ->withProperties(['company_id' => $lockedCompensation->company_id])
                ->log('submitted employment compensation');

            return $lockedCompensation;
        });
    }
}
