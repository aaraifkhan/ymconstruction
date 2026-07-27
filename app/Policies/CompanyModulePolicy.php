<?php

namespace App\Policies;

use App\Models\CompanyModule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:CompanyModule')
            && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, CompanyModule $companyModule): bool
    {
        return $this->hasPermission($user, 'View:CompanyModule')
            && $user->canAccessTenant($companyModule->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:CompanyModule')
            && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, CompanyModule $companyModule): bool
    {
        return $this->hasPermission($user, 'Update:CompanyModule')
            && $user->canAccessTenant($companyModule->company);
    }

    public function delete(User $user, CompanyModule $companyModule): bool
    {
        return $this->hasPermission($user, 'Delete:CompanyModule')
            && $user->canAccessTenant($companyModule->company);
    }

    public function restore(User $user, CompanyModule $companyModule): bool
    {
        return false;
    }

    public function forceDelete(User $user, CompanyModule $companyModule): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:CompanyModule')
            && $this->canAccessCurrentCompany($user);
    }

    public function restoreAny(User $user): bool
    {
        return false;
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
