<?php

namespace App\Policies;

use App\Models\Employment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmploymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Employment') && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'View:Employment') && $user->canAccessTenant($employment->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Employment') && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'Update:Employment') && $user->canAccessTenant($employment->company);
    }

    public function delete(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'Delete:Employment') && $user->canAccessTenant($employment->company);
    }

    public function restore(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'Restore:Employment') && $user->canAccessTenant($employment->company);
    }

    public function forceDelete(User $user, Employment $employment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:Employment') && $this->canAccessCurrentCompany($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Employment') && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function viewHrNotes(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'ViewHrNotes:Employment') && $user->canAccessTenant($employment->company);
    }

    public function manageHrVerification(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'ManageHrVerification:Employment') && $user->canAccessTenant($employment->company);
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
