<?php

namespace App\Policies;

class FinalSettlementLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'FinalSettlementLine';
}
