<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceCorrection';

    public function approve(User $user, AttendanceCorrection $correction): bool
    {
        return $this->hasPermission($user, 'Approve:AttendanceCorrection') && $this->canAccessRecord($user, $correction);
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }
}
