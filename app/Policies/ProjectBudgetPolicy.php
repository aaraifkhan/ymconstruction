<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectBudgetPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ProjectBudget';

    public function update(User $user, Model $projectBudget): bool
    {
        return parent::update($user, $projectBudget) && $projectBudget->isDraft();
    }

    public function delete(User $user, Model $projectBudget): bool
    {
        return parent::delete($user, $projectBudget) && $projectBudget->isDraft();
    }

    public function approve(User $user, Model $projectBudget): bool
    {
        return $this->hasPermission($user, 'Approve:ProjectBudget')
            && $this->canAccessRecord($user, $projectBudget)
            && $projectBudget->isDraft();
    }
}
