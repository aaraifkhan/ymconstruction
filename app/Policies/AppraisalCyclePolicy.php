<?php

namespace App\Policies;

use App\Enums\AppraisalCycleStatus;
use App\Models\AppraisalCycle;
use App\Models\User;

class AppraisalCyclePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AppraisalCycle';

    public function activate(User $user, AppraisalCycle $cycle): bool
    {
        return $this->workflow($user, $cycle, 'Activate')
            && $cycle->status === AppraisalCycleStatus::Draft;
    }

    public function close(User $user, AppraisalCycle $cycle): bool
    {
        return $this->workflow($user, $cycle, 'Close')
            && $cycle->status === AppraisalCycleStatus::Active;
    }

    private function workflow(User $user, AppraisalCycle $cycle, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:AppraisalCycle")
            && $this->canAccessRecord($user, $cycle);
    }
}
