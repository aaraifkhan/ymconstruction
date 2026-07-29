<?php

namespace App\Policies;

class ClearanceChecklistTemplatePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ClearanceChecklistTemplate';
}
