<?php

namespace App\Policies;

class AccountingMappingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AccountingMapping';
}
