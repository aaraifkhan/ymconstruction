<?php

namespace App\Policies;

class FinancialYearPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'FinancialYear';
}
