<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Department') && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'View:Department') && $user->canAccessTenant($department->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Department') && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'Update:Department') && $user->canAccessTenant($department->company);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'Delete:Department')
            && $user->canAccessTenant($department->company)
            && ! $department->employments()->exists();
    }

    public function restore(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'Restore:Department') && $user->canAccessTenant($department->company);
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Department') && $this->canAccessCurrentCompany($user);
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
