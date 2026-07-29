<?php

namespace App\Policies;

class LeavePolicyPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'LeavePolicy';
}
