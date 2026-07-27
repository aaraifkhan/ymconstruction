<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'Project';

    public function delete(User $user, Model $project): bool
    {
        return parent::delete($user, $project)
            && ! $project->sites()->exists()
            && ! $project->budgets()->exists()
            && ! $project->documents()->exists();
    }
}
