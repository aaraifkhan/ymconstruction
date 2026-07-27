<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TreasuryAllocationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'TreasuryAllocation';

    public function update(User $user, Model $allocation): bool
    {
        return parent::update($user, $allocation) && $allocation->treasuryTransaction->isEditable();
    }

    public function delete(User $user, Model $allocation): bool
    {
        return parent::delete($user, $allocation) && $allocation->treasuryTransaction->isEditable();
    }
}
