<?php

namespace App\Policies;

class ProcurementApprovalRulePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ProcurementApprovalRule';
}
