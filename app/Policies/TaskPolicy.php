<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Task') && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Task $task): bool
    {
        if (! $user->canAccessTenant($task->company)) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('department_head') || $this->hasPermission($user, 'ViewAll:Task')) {
            return true;
        }

        $employmentId = $user->employee?->employments()->where('company_id', $task->company_id)->value('id');

        if ($employmentId && (int) $task->assigned_to_employment_id === (int) $employmentId) {
            return true;
        }

        // Check if user is lead of the task's team
        if ($task->department_team_id && $employmentId) {
            $isLead = $task->departmentTeam?->team_lead_id === $employmentId;
            if ($isLead) {
                return true;
            }
        }

        return $this->hasPermission($user, 'View:Task');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Task') && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Task $task): bool
    {
        if (! $user->canAccessTenant($task->company)) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('department_head') || $this->hasPermission($user, 'ManageAll:Task')) {
            return true;
        }

        $employmentId = $user->employee?->employments()->where('company_id', $task->company_id)->value('id');

        // Member can update their own in-progress task (e.g. progress % or submit)
        if ($employmentId && (int) $task->assigned_to_employment_id === (int) $employmentId) {
            return true;
        }

        // Lead can update their team tasks
        if ($task->department_team_id && $employmentId && $task->departmentTeam?->team_lead_id === $employmentId) {
            return true;
        }

        return $this->hasPermission($user, 'Update:Task');
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->hasPermission($user, 'Delete:Task') && $user->canAccessTenant($task->company);
    }

    public function restore(User $user, Task $task): bool
    {
        return $this->hasPermission($user, 'Restore:Task') && $user->canAccessTenant($task->company);
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Task') && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canAccessCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
