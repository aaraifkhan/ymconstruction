<?php

namespace App\Policies;

use App\Models\AttendancePunch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendancePunchPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendancePunch';

    public function approve(User $user, AttendancePunch $punch): bool
    {
        return $this->hasPermission($user, 'Approve:AttendancePunch') && $this->canAccessRecord($user, $punch);
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }
}
