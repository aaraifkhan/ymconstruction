<?php

namespace App\Policies;

class FinalSettlementAccountMappingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'FinalSettlementAccountMapping';
}
