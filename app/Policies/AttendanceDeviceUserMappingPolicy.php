<?php

namespace App\Policies;

class AttendanceDeviceUserMappingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceDeviceUserMapping';
}
