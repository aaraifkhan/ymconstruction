<?php

namespace App\Policies;

class PayrollCalculationRulePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PayrollCalculationRule';
}
