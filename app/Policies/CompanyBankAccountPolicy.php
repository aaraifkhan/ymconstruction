<?php

namespace App\Policies;

use App\Models\CompanyBankAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyBankAccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:CompanyBankAccount')
            && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, CompanyBankAccount $companyBankAccount): bool
    {
        return $this->hasPermission($user, 'View:CompanyBankAccount')
            && $user->canAccessTenant($companyBankAccount->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:CompanyBankAccount')
            && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, CompanyBankAccount $companyBankAccount): bool
    {
        return $this->hasPermission($user, 'Update:CompanyBankAccount')
            && $user->canAccessTenant($companyBankAccount->company);
    }

    public function delete(User $user, CompanyBankAccount $companyBankAccount): bool
    {
        return $this->hasPermission($user, 'Delete:CompanyBankAccount')
            && $user->canAccessTenant($companyBankAccount->company);
    }

    public function restore(User $user, CompanyBankAccount $companyBankAccount): bool
    {
        return $this->hasPermission($user, 'Restore:CompanyBankAccount')
            && $user->canAccessTenant($companyBankAccount->company);
    }

    public function forceDelete(User $user, CompanyBankAccount $companyBankAccount): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:CompanyBankAccount')
            && $this->canAccessCurrentCompany($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:CompanyBankAccount')
            && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function viewSensitive(User $user, CompanyBankAccount $companyBankAccount): bool
    {
        return $this->hasPermission($user, 'ViewSensitive:CompanyBankAccount')
            && $user->canAccessTenant($companyBankAccount->company);
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
