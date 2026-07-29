<?php

namespace App\Policies;

use App\Enums\PerformanceAppraisalStatus;
use App\Models\PerformanceAppraisal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PerformanceAppraisalPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PerformanceAppraisal';

    public function update(User $user, Model $record): bool
    {
        return parent::update($user, $record) && in_array($record->status, [
            PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected,
        ], true);
    }

    public function submit(User $user, PerformanceAppraisal $record): bool
    {
        return $this->workflow($user, $record, 'Submit');
    }

    public function review(User $user, PerformanceAppraisal $record): bool
    {
        return $this->workflow($user, $record, 'Review');
    }

    public function approve(User $user, PerformanceAppraisal $record): bool
    {
        return $this->workflow($user, $record, 'Approve');
    }

    public function acknowledge(User $user, PerformanceAppraisal $record): bool
    {
        return $this->workflow($user, $record, 'Acknowledge');
    }

    public function reject(User $user, PerformanceAppraisal $record): bool
    {
        return $this->workflow($user, $record, 'Reject');
    }

    private function workflow(User $user, PerformanceAppraisal $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:PerformanceAppraisal")
            && $this->canAccessRecord($user, $record);
    }
}
