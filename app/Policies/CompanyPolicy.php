<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Company');
    }

    public function view(User $user, Company $company): bool
    {
        return $this->hasPermission($user, 'View:Company')
            && $this->canAccessCompany($user, $company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Company');
    }

    public function update(User $user, Company $company): bool
    {
        return $this->hasPermission($user, 'Update:Company')
            && $this->canAccessCompany($user, $company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $this->hasPermission($user, 'Delete:Company')
            && $this->canAccessCompany($user, $company);
    }

    public function restore(User $user, Company $company): bool
    {
        return $this->hasPermission($user, 'Restore:Company')
            && $this->canAccessCompany($user, $company);
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:Company');
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Company');
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function manageMembers(User $user, Company $company): bool
    {
        return $this->hasPermission($user, 'ManageMembers:Company')
            && $this->canAccessCompany($user, $company);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }

    private function canAccessCompany(User $user, Company $company): bool
    {
        return $user->hasRole('super_admin')
            || $user->getAccessibleCompanies()->contains(
                fn (Company $accessibleCompany): bool => $accessibleCompany->is($company)
            );
    }
}
