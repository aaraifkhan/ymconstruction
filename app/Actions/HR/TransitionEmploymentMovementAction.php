<?php

namespace App\Actions\HR;

use App\Enums\EmploymentMovementStatus;
use App\Models\Employment;
use App\Models\EmploymentMovementRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransitionEmploymentMovementAction
{
    public function submit(EmploymentMovementRequest $movement, User $actor): EmploymentMovementRequest
    {
        Gate::forUser($actor)->authorize('submit', $movement);

        return DB::transaction(function () use ($movement, $actor): EmploymentMovementRequest {
            $movement = EmploymentMovementRequest::query()->with('employment')
                ->whereKey($movement)->lockForUpdate()->firstOrFail();
            if (! in_array($movement->status, [
                EmploymentMovementStatus::Draft, EmploymentMovementStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected movements may be submitted.']);
            }
            $movement->update([
                'reference_number' => filled($movement->reference_number)
                    ? $movement->reference_number
                    : sprintf('MOV-%06d', $movement->getKey()),
                'status' => EmploymentMovementStatus::PendingApproval,
                'before_snapshot' => $this->snapshot($movement->employment),
                'target_snapshot' => $this->targetSnapshot($movement),
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            $this->audit($movement, $actor, 'submitted');

            return $movement;
        }, 3);
    }

    public function approve(EmploymentMovementRequest $movement, User $actor): EmploymentMovementRequest
    {
        Gate::forUser($actor)->authorize('approve', $movement);

        return DB::transaction(function () use ($movement, $actor): EmploymentMovementRequest {
            $movement = EmploymentMovementRequest::query()->whereKey($movement)->lockForUpdate()->firstOrFail();
            if ($movement->status !== EmploymentMovementStatus::PendingApproval
                || in_array((int) $actor->getKey(), [
                    (int) $movement->created_by_id, (int) $movement->submitted_by_id,
                ], true)) {
                throw ValidationException::withMessages(['status' => 'Movement approval requires an independent approver.']);
            }
            $movement->update([
                'status' => EmploymentMovementStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            $this->audit($movement, $actor, 'approved');

            if ($movement->effective_on->lte(today())) {
                return $this->apply($movement, $actor);
            }

            return $movement;
        }, 3);
    }

    public function apply(EmploymentMovementRequest $movement, User $actor): EmploymentMovementRequest
    {
        Gate::forUser($actor)->authorize('apply', $movement);

        return DB::transaction(function () use ($movement, $actor): EmploymentMovementRequest {
            $movement = EmploymentMovementRequest::query()->whereKey($movement)->lockForUpdate()->firstOrFail();
            if ($movement->status === EmploymentMovementStatus::Applied) {
                return $movement;
            }
            if ($movement->status !== EmploymentMovementStatus::Approved || $movement->effective_on->isFuture()) {
                throw ValidationException::withMessages(['effective_on' => 'Only an approved movement that is due may be applied.']);
            }
            $employment = Employment::query()->whereKey($movement->employment_id)->lockForUpdate()->firstOrFail();
            $employment->recordApprovedChangeContext(
                $movement->type->value,
                CarbonImmutable::parse($movement->effective_on),
                $actor,
            );
            $employment->update(array_filter([
                'department_id' => $movement->target_department_id,
                'designation_id' => $movement->target_designation_id,
                'reporting_to_employment_id' => $movement->target_reporting_employment_id,
                'work_location_id' => $movement->target_work_location_id,
                'employment_category' => $movement->target_employment_category,
            ], fn ($value) => $value !== null));
            $movement->update([
                'status' => EmploymentMovementStatus::Applied,
                'applied_by_id' => $actor->getKey(),
                'applied_at' => now(),
            ]);
            $this->audit($movement, $actor, 'applied');

            return $movement;
        }, 3);
    }

    public function reject(EmploymentMovementRequest $movement, string $reason, User $actor): EmploymentMovementRequest
    {
        Gate::forUser($actor)->authorize('reject', $movement);

        return DB::transaction(function () use ($movement, $reason, $actor): EmploymentMovementRequest {
            $movement = EmploymentMovementRequest::query()->whereKey($movement)->lockForUpdate()->firstOrFail();
            if ($movement->status !== EmploymentMovementStatus::PendingApproval || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'A pending movement and rejection reason are required.']);
            }
            $movement->update([
                'status' => EmploymentMovementStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $this->audit($movement, $actor, 'rejected');

            return $movement;
        }, 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(Employment $employment): array
    {
        return $employment->only([
            'department_id', 'designation_id', 'reporting_to_employment_id',
            'work_location_id', 'employment_category',
        ]);
    }

    /** @return array<string, mixed> */
    private function targetSnapshot(EmploymentMovementRequest $movement): array
    {
        return [
            'department_id' => $movement->target_department_id,
            'designation_id' => $movement->target_designation_id,
            'reporting_to_employment_id' => $movement->target_reporting_employment_id,
            'work_location_id' => $movement->target_work_location_id,
            'employment_category' => $movement->target_employment_category?->value,
            'employment_compensation_id' => $movement->employment_compensation_id,
        ];
    }

    private function audit(EmploymentMovementRequest $movement, User $actor, string $event): void
    {
        activity('employment_movements')->causedBy($actor)->performedOn($movement)
            ->event($event)->withProperties([
                'company_id' => $movement->company_id,
                'reference_number' => $movement->reference_number,
                'effective_on' => $movement->effective_on->toDateString(),
            ])->log("{$event} employment movement");
    }
}
