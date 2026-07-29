<?php

namespace App\Policies;

use App\Models\EmploymentSeparation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmploymentSeparationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmploymentSeparation';

    public function viewAny(User $user): bool
    {
        return parent::viewAny($user) && $this->hasPermission($user, 'ViewSensitive:EmploymentSeparation');
    }

    public function view(User $user, Model $record): bool
    {
        return parent::view($user, $record) && $this->hasPermission($user, 'ViewSensitive:EmploymentSeparation');
    }

    public function submit(User $user, EmploymentSeparation $record): bool
    {
        return $this->workflow($user, $record, 'Submit');
    }

    public function accept(User $user, EmploymentSeparation $record): bool
    {
        return $this->workflow($user, $record, 'Accept');
    }

    public function approve(User $user, EmploymentSeparation $record): bool
    {
        return $this->workflow($user, $record, 'Approve');
    }

    public function withdraw(User $user, EmploymentSeparation $record): bool
    {
        return $this->workflow($user, $record, 'Withdraw');
    }

    public function reject(User $user, EmploymentSeparation $record): bool
    {
        return $this->workflow($user, $record, 'Reject');
    }

    public function reviewAccess(User $user, EmploymentSeparation $record): bool
    {
        return $this->workflow($user, $record, 'ReviewAccess');
    }

    private function workflow(User $user, EmploymentSeparation $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:EmploymentSeparation")
            && $this->canAccessRecord($user, $record);
    }
}
