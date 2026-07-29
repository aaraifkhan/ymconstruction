<?php

namespace App\Policies;

class LeaveTypePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'LeaveType';
}
