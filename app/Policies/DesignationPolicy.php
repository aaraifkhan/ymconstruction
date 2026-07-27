<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class DesignationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Designation') && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Designation $designation): bool
    {
        return $this->hasPermission($user, 'View:Designation') && $user->canAccessTenant($designation->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Designation') && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Designation $designation): bool
    {
        return $this->hasPermission($user, 'Update:Designation') && $user->canAccessTenant($designation->company);
    }

    public function delete(User $user, Designation $designation): bool
    {
        return $this->hasPermission($user, 'Delete:Designation')
            && $user->canAccessTenant($designation->company)
            && ! $designation->employments()->exists();
    }

    public function restore(User $user, Designation $designation): bool
    {
        return $this->hasPermission($user, 'Restore:Designation') && $user->canAccessTenant($designation->company);
    }

    public function forceDelete(User $user, Designation $designation): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Designation') && $this->canAccessCurrentCompany($user);
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
