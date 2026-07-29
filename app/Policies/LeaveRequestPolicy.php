<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'LeaveRequest';

    public function submit(User $user, LeaveRequest $request): bool
    {
        return $this->workflowPermission($user, 'Submit', $request);
    }

    public function managerApprove(User $user, LeaveRequest $request): bool
    {
        return $this->workflowPermission($user, 'ManagerApprove', $request);
    }

    public function approve(User $user, LeaveRequest $request): bool
    {
        return $this->workflowPermission($user, 'Approve', $request);
    }

    public function reject(User $user, LeaveRequest $request): bool
    {
        return $this->workflowPermission($user, 'Reject', $request);
    }

    public function cancel(User $user, LeaveRequest $request): bool
    {
        return $this->workflowPermission($user, 'Cancel', $request);
    }

    private function workflowPermission(User $user, string $action, LeaveRequest $request): bool
    {
        return $this->hasPermission($user, "{$action}:LeaveRequest") && $this->canAccessRecord($user, $request);
    }
}
