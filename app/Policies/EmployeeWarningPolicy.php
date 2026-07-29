<?php

namespace App\Policies;

use App\Models\EmployeeWarning;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeWarningPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeWarning';

    public function viewAny(User $user): bool
    {
        return parent::viewAny($user) && $this->hasPermission($user, 'ViewSensitive:EmployeeWarning');
    }

    public function view(User $user, Model $record): bool
    {
        return parent::view($user, $record) && $this->hasPermission($user, 'ViewSensitive:EmployeeWarning');
    }

    public function issue(User $user, EmployeeWarning $record): bool
    {
        return $this->workflow($user, $record, 'Issue');
    }

    public function respond(User $user, EmployeeWarning $record): bool
    {
        return $this->workflow($user, $record, 'Respond');
    }

    public function acknowledge(User $user, EmployeeWarning $record): bool
    {
        return $this->workflow($user, $record, 'Acknowledge');
    }

    public function close(User $user, EmployeeWarning $record): bool
    {
        return $this->workflow($user, $record, 'Close');
    }

    private function workflow(User $user, EmployeeWarning $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:EmployeeWarning")
            && $this->canAccessRecord($user, $record);
    }
}
