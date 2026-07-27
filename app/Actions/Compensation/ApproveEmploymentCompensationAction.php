<?php

namespace App\Actions\Compensation;

use App\Enums\CompensationStatus;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveEmploymentCompensationAction
{
    public function handle(EmploymentCompensation $compensation, User $actor): EmploymentCompensation
    {
        return DB::transaction(function () use ($actor, $compensation): EmploymentCompensation {
            $lockedCompensation = EmploymentCompensation::query()
                ->whereKey($compensation)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('approve', $lockedCompensation);

            if ($lockedCompensation->status !== CompensationStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'employment_compensation' => 'Only submitted compensation can be approved.',
                ]);
            }

            $approvedCompensations = EmploymentCompensation::query()
                ->where('employment_id', $lockedCompensation->employment_id)
                ->where('status', CompensationStatus::Approved)
                ->orderBy('effective_from')
                ->lockForUpdate()
                ->get();

            $futureConflict = $approvedCompensations->first(function (EmploymentCompensation $approved) use ($lockedCompensation): bool {
                if ($approved->effective_from->lt($lockedCompensation->effective_from)) {
                    return false;
                }

                return $lockedCompensation->effective_to === null
                    || $approved->effective_from->lte($lockedCompensation->effective_to);
            });

            if ($futureConflict !== null) {
                throw ValidationException::withMessages([
                    'effective_from' => 'This period overlaps approved compensation starting on or after the selected date.',
                ]);
            }

            $previousOverlaps = $approvedCompensations->filter(
                fn (EmploymentCompensation $approved): bool => $approved->effective_from->lt($lockedCompensation->effective_from)
                    && ($approved->effective_to === null || $approved->effective_to->gte($lockedCompensation->effective_from)),
            );

            if ($previousOverlaps->count() > 1) {
                throw ValidationException::withMessages([
                    'employment_compensation' => 'Existing approved compensation history contains overlapping periods.',
                ]);
            }

            $previousOverlaps->each(function (EmploymentCompensation $approved) use ($actor, $lockedCompensation): void {
                $approved->update([
                    'effective_to' => $lockedCompensation->effective_from->copy()->subDay(),
                ]);

                activity('employment_compensations')
                    ->causedBy($actor)
                    ->performedOn($approved)
                    ->event('superseded')
                    ->withProperties([
                        'company_id' => $approved->company_id,
                        'superseded_by_id' => $lockedCompensation->getKey(),
                    ])
                    ->log('closed previous compensation period');
            });

            $lockedCompensation->update([
                'status' => CompensationStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('employment_compensations')
                ->causedBy($actor)
                ->performedOn($lockedCompensation)
                ->event('approved')
                ->withProperties(['company_id' => $lockedCompensation->company_id])
                ->log('approved employment compensation');

            return $lockedCompensation;
        });
    }
}
