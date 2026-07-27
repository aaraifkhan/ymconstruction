<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\EmployeeBankAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeBankAccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:EmployeeBankAccount') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, EmployeeBankAccount $bankAccount): bool
    {
        return $this->allowed($user, 'View:EmployeeBankAccount') && $this->belongsToCurrentCompany($user, $bankAccount);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:EmployeeBankAccount') && $this->hasCurrentCompany($user);
    }

    public function update(User $user, EmployeeBankAccount $bankAccount): bool
    {
        return $this->allowed($user, 'Update:EmployeeBankAccount') && $this->belongsToCurrentCompany($user, $bankAccount);
    }

    public function delete(User $user, EmployeeBankAccount $bankAccount): bool
    {
        return $this->allowed($user, 'Delete:EmployeeBankAccount') && $this->belongsToCurrentCompany($user, $bankAccount);
    }

    public function restore(User $user, EmployeeBankAccount $bankAccount): bool
    {
        return $this->allowed($user, 'Restore:EmployeeBankAccount') && $this->belongsToCurrentCompany($user, $bankAccount);
    }

    public function viewSensitive(User $user, EmployeeBankAccount $bankAccount): bool
    {
        return $this->allowed($user, 'ViewSensitive:EmployeeBankAccount') && $this->belongsToCurrentCompany($user, $bankAccount);
    }

    public function forceDelete(User $user, EmployeeBankAccount $bankAccount): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function belongsToCurrentCompany(User $user, EmployeeBankAccount $bankAccount): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company
            && $user->canAccessTenant($company)
            && $bankAccount->employee->isEmployedBy($company);
    }

    private function hasCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company && $user->canAccessTenant($company);
    }

    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
