<?php

namespace App\Policies;

use App\Models\EmployeeClearanceItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeClearanceItemPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeClearanceItem';

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function decide(User $user, EmployeeClearanceItem $record): bool
    {
        return $this->hasPermission($user, $record->area->permissionAbility())
            && $this->canAccessRecord($user, $record);
    }

    public function waive(User $user, EmployeeClearanceItem $record): bool
    {
        return $this->hasPermission($user, 'Waive:EmployeeClearance')
            && $this->canAccessRecord($user, $record);
    }

    public function recommendRecovery(User $user, EmployeeClearanceItem $record): bool
    {
        return $this->hasPermission($user, 'RecommendRecovery:EmployeeClearance')
            && $this->canAccessRecord($user, $record);
    }
}
