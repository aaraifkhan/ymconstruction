<?php

namespace App\Policies;

use App\Models\FinancialPeriod;
use App\Models\User;

class FinancialPeriodPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'FinancialPeriod';

    public function close(User $user, FinancialPeriod $period): bool
    {
        return $this->hasPermission($user, 'Close:FinancialPeriod') && $this->canAccessRecord($user, $period);
    }

    public function lock(User $user, FinancialPeriod $period): bool
    {
        return $this->hasPermission($user, 'Lock:FinancialPeriod') && $this->canAccessRecord($user, $period);
    }

    public function reopen(User $user, FinancialPeriod $period): bool
    {
        return $this->hasPermission($user, 'Reopen:FinancialPeriod') && $this->canAccessRecord($user, $period);
    }
}
