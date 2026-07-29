<?php

namespace App\Policies;

class AttendanceRulePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceRule';
}
