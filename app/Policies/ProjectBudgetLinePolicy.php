<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectBudgetLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ProjectBudgetLine';

    public function update(User $user, Model $projectBudgetLine): bool
    {
        return parent::update($user, $projectBudgetLine) && $projectBudgetLine->budget->isDraft();
    }

    public function delete(User $user, Model $projectBudgetLine): bool
    {
        return parent::delete($user, $projectBudgetLine) && $projectBudgetLine->budget->isDraft();
    }
}
