<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceRecord';

    public function finalize(User $user, AttendanceRecord $record): bool
    {
        return $this->hasPermission($user, 'Finalize:AttendanceRecord') && $this->canAccessRecord($user, $record);
    }
}
