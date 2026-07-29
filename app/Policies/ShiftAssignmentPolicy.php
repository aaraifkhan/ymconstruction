<?php

namespace App\Policies;

class ShiftAssignmentPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ShiftAssignment';
}
