<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CostCenterPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'CostCenter';

    public function delete(User $user, Model $costCenter): bool
    {
        return parent::delete($user, $costCenter)
            && ! $costCenter->projectSites()->exists()
            && ! $costCenter->budgetLines()->exists();
    }
}
