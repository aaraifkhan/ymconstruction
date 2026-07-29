<?php

namespace App\Policies;

use App\Models\EmployeeClearance;
use App\Models\EmploymentSeparation;
use App\Models\User;

class EmployeeClearancePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeClearance';

    public function prepare(User $user, EmploymentSeparation $separation): bool
    {
        return $this->hasPermission($user, 'Prepare:EmployeeClearance')
            && $this->canAccessRecord($user, $separation);
    }

    public function submit(User $user, EmployeeClearance $record): bool
    {
        return $this->workflow($user, $record, 'Submit');
    }

    public function refresh(User $user, EmployeeClearance $record): bool
    {
        return $this->workflow($user, $record, 'Refresh');
    }

    public function complete(User $user, EmployeeClearance $record): bool
    {
        return $this->workflow($user, $record, 'Complete');
    }

    private function workflow(User $user, EmployeeClearance $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:EmployeeClearance")
            && $this->canAccessRecord($user, $record);
    }
}
