<?php

namespace App\Policies;

use App\Models\AttendanceDevice;
use App\Models\User;

class AttendanceDevicePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceDevice';

    public function sync(User $user, AttendanceDevice $device): bool
    {
        return $this->hasPermission($user, 'Sync:AttendanceDevice') && $this->canAccessRecord($user, $device);
    }
}
