<?php

namespace App\Actions\Compensation;

use App\Enums\CompensationStatus;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectEmploymentCompensationAction
{
    public function handle(
        EmploymentCompensation $compensation,
        User $actor,
        string $reason,
    ): EmploymentCompensation {
        return DB::transaction(function () use ($actor, $compensation, $reason): EmploymentCompensation {
            $lockedCompensation = EmploymentCompensation::query()
                ->whereKey($compensation)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('reject', $lockedCompensation);

            if ($lockedCompensation->status !== CompensationStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'employment_compensation' => 'Only submitted compensation can be rejected.',
                ]);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'A rejection reason is required.',
                ]);
            }

            $lockedCompensation->update([
                'status' => CompensationStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_by_id' => null,
                'approved_at' => null,
            ]);

            activity('employment_compensations')
                ->causedBy($actor)
                ->performedOn($lockedCompensation)
                ->event('rejected')
                ->withProperties([
                    'company_id' => $lockedCompensation->company_id,
                    'reason' => $reason,
                ])
                ->log('rejected employment compensation');

            return $lockedCompensation;
        });
    }
}
