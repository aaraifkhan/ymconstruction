<?php

namespace App\Policies;

class BankReconciliationMatchPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'BankReconciliationMatch';
}
