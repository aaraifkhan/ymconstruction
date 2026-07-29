<?php

namespace App\Policies;

class WarningLetterTemplatePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'WarningLetterTemplate';
}
