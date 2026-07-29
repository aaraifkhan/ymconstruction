<?php

namespace App\Policies;

use App\Models\EmploymentMovementRequest;
use App\Models\User;

class EmploymentMovementRequestPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmploymentMovementRequest';

    public function submit(User $user, EmploymentMovementRequest $record): bool
    {
        return $this->workflow($user, $record, 'Submit');
    }

    public function approve(User $user, EmploymentMovementRequest $record): bool
    {
        return $this->workflow($user, $record, 'Approve');
    }

    public function apply(User $user, EmploymentMovementRequest $record): bool
    {
        return $this->workflow($user, $record, 'Apply');
    }

    public function reject(User $user, EmploymentMovementRequest $record): bool
    {
        return $this->workflow($user, $record, 'Reject');
    }

    private function workflow(User $user, EmploymentMovementRequest $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:EmploymentMovementRequest")
            && $this->canAccessRecord($user, $record);
    }
}
