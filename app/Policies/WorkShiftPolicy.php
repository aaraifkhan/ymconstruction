<?php

namespace App\Policies;

class WorkShiftPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'WorkShift';
}
