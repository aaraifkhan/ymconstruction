<?php

namespace App\Policies;

use App\Models\DepartmentTeam;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentTeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:DepartmentTeam') && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, DepartmentTeam $team): bool
    {
        return $this->hasPermission($user, 'View:DepartmentTeam') && $user->canAccessTenant($team->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:DepartmentTeam') && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, DepartmentTeam $team): bool
    {
        return $this->hasPermission($user, 'Update:DepartmentTeam') && $user->canAccessTenant($team->company);
    }

    public function delete(User $user, DepartmentTeam $team): bool
    {
        return $this->hasPermission($user, 'Delete:DepartmentTeam')
            && $user->canAccessTenant($team->company)
            && ! $team->tasks()->exists()
            && ! $team->members()->exists();
    }

    public function restore(User $user, DepartmentTeam $team): bool
    {
        return $this->hasPermission($user, 'Restore:DepartmentTeam') && $user->canAccessTenant($team->company);
    }

    public function forceDelete(User $user, DepartmentTeam $team): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:DepartmentTeam') && $this->canAccessCurrentCompany($user);
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
