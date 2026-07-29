<?php

namespace App\Actions\HR;

use App\Enums\EmploymentAccessReviewStatus;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\EmploymentSeparationType;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Employment;
use App\Models\EmploymentSeparation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransitionEmploymentSeparationAction
{
    public function submit(EmploymentSeparation $separation, User $actor): EmploymentSeparation
    {
        Gate::forUser($actor)->authorize('submit', $separation);

        return DB::transaction(function () use ($separation, $actor): EmploymentSeparation {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            if (! in_array($separation->status, [
                EmploymentSeparationStatus::Draft, EmploymentSeparationStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected separations may be submitted.']);
            }
            $separation->update([
                'reference_number' => filled($separation->reference_number)
                    ? $separation->reference_number
                    : sprintf('SEP-%06d', $separation->getKey()),
                'status' => EmploymentSeparationStatus::Submitted,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            $this->audit($separation, $actor, 'submitted');

            return $separation;
        }, 3);
    }

    public function acceptResignation(EmploymentSeparation $separation, User $actor): EmploymentSeparation
    {
        Gate::forUser($actor)->authorize('accept', $separation);

        return DB::transaction(function () use ($separation, $actor): EmploymentSeparation {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            if ($separation->type !== EmploymentSeparationType::Resignation
                || $separation->status !== EmploymentSeparationStatus::Submitted
                || (int) $separation->submitted_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'A submitted resignation requires independent acceptance.']);
            }
            $separation->update([
                'status' => EmploymentSeparationStatus::Accepted,
                'accepted_by_id' => $actor->getKey(),
                'accepted_at' => now(),
            ]);
            $this->audit($separation, $actor, 'accepted');

            return $separation;
        }, 3);
    }

    public function approve(
        EmploymentSeparation $separation,
        CarbonImmutable $lastWorkingDate,
        User $actor,
    ): EmploymentSeparation {
        Gate::forUser($actor)->authorize('approve', $separation);

        return DB::transaction(function () use ($separation, $lastWorkingDate, $actor): EmploymentSeparation {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            $expected = $separation->type === EmploymentSeparationType::Resignation
                ? EmploymentSeparationStatus::Accepted
                : EmploymentSeparationStatus::Submitted;
            if ($separation->status !== $expected
                || in_array((int) $actor->getKey(), [
                    (int) $separation->created_by_id,
                    (int) $separation->submitted_by_id,
                    (int) $separation->accepted_by_id,
                ], true)
                || $lastWorkingDate->lt($separation->employment()->value('joining_date'))) {
                throw ValidationException::withMessages(['status' => 'Separation approval requires the correct workflow state, date, and independent approver.']);
            }
            $employment = Employment::query()->whereKey($separation->employment_id)->lockForUpdate()->firstOrFail();
            if ($employment->leaveRequests()
                ->whereNotIn('status', [
                    LeaveRequestStatus::Rejected->value,
                    LeaveRequestStatus::Cancelled->value,
                ])
                ->whereDate('ends_on', '>', $lastWorkingDate)
                ->exists()) {
                throw ValidationException::withMessages([
                    'approved_last_working_date' => 'Cancel or revise Leave requests extending beyond the approved last working date.',
                ]);
            }
            $employment->recordApprovedChangeContext($separation->type->value, $lastWorkingDate, $actor);
            $employment->update([
                'ending_date' => $lastWorkingDate,
                'employment_status' => $separation->type === EmploymentSeparationType::Resignation
                    ? EmploymentStatus::Resigned
                    : EmploymentStatus::Terminated,
            ]);
            $accessStatus = $employment->employee()->whereNotNull('user_id')->exists()
                ? EmploymentAccessReviewStatus::Pending
                : EmploymentAccessReviewStatus::NotApplicable;
            $separation->update([
                'status' => EmploymentSeparationStatus::Approved,
                'approved_last_working_date' => $lastWorkingDate,
                'access_review_status' => $accessStatus,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            $this->audit($separation, $actor, 'approved');

            return $separation;
        }, 3);
    }

    public function withdraw(EmploymentSeparation $separation, string $reason, User $actor): EmploymentSeparation
    {
        Gate::forUser($actor)->authorize('withdraw', $separation);

        return $this->decision($separation, $actor, $reason, 'withdrawn');
    }

    public function reject(EmploymentSeparation $separation, string $reason, User $actor): EmploymentSeparation
    {
        Gate::forUser($actor)->authorize('reject', $separation);

        return $this->decision($separation, $actor, $reason, 'rejected');
    }

    public function completeAccessReview(EmploymentSeparation $separation, User $actor): EmploymentSeparation
    {
        Gate::forUser($actor)->authorize('reviewAccess', $separation);

        return DB::transaction(function () use ($separation, $actor): EmploymentSeparation {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            if ($separation->status !== EmploymentSeparationStatus::Approved
                || $separation->access_review_status !== EmploymentAccessReviewStatus::Pending) {
                throw ValidationException::withMessages(['access_review_status' => 'No pending access review exists.']);
            }
            $separation->update([
                'access_review_status' => EmploymentAccessReviewStatus::Completed,
                'access_reviewed_by_id' => $actor->getKey(),
                'access_reviewed_at' => now(),
            ]);
            $this->audit($separation, $actor, 'access_reviewed');

            return $separation;
        }, 3);
    }

    private function decision(
        EmploymentSeparation $separation,
        User $actor,
        string $reason,
        string $event,
    ): EmploymentSeparation {
        return DB::transaction(function () use ($separation, $actor, $reason, $event): EmploymentSeparation {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            if (! in_array($separation->status, [
                EmploymentSeparationStatus::Submitted, EmploymentSeparationStatus::Accepted,
            ], true) || blank($reason)
                || ($event === 'withdrawn' && $separation->type !== EmploymentSeparationType::Resignation)) {
                throw ValidationException::withMessages(['status' => 'This separation decision is not allowed from the current state.']);
            }
            $separation->update($event === 'withdrawn' ? [
                'status' => EmploymentSeparationStatus::Withdrawn,
                'withdrawn_by_id' => $actor->getKey(),
                'withdrawn_at' => now(),
                'withdrawal_reason' => $reason,
            ] : [
                'status' => EmploymentSeparationStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $this->audit($separation, $actor, $event);

            return $separation;
        }, 3);
    }

    private function audit(EmploymentSeparation $separation, User $actor, string $event): void
    {
        activity('employment_separations')->causedBy($actor)->performedOn($separation)
            ->event($event)->withProperties([
                'company_id' => $separation->company_id,
                'reference_number' => $separation->reference_number,
                'type' => $separation->type->value,
            ])->log("{$event} employment separation");
    }
}
