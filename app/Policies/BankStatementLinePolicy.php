<?php

namespace App\Policies;

class BankStatementLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'BankStatementLine';
}
