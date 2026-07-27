<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Employee')
            && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'View:Employee')
            && $this->belongsToCurrentCompany($user, $employee);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Employee')
            && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'Update:Employee')
            && $this->belongsToCurrentCompany($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'Delete:Employee')
            && $this->belongsToCurrentCompany($user, $employee)
            && ! $employee->employments()->withTrashed()->exists();
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'Restore:Employee')
            && $this->belongsToCurrentCompany($user, $employee);
    }

    public function forceDelete(User $user, Employee $employee): bool
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

    public function viewIdentity(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'ViewIdentity:Employee')
            && $this->belongsToCurrentCompany($user, $employee);
    }

    public function viewContact(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'ViewContact:Employee')
            && $this->belongsToCurrentCompany($user, $employee);
    }

    public function viewMedical(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'ViewMedical:Employee')
            && $this->belongsToCurrentCompany($user, $employee);
    }

    public function manageSensitive(User $user, ?Employee $employee = null): bool
    {
        if (! $this->hasPermission($user, 'ManageSensitive:Employee')) {
            return false;
        }

        return $employee === null
            ? $this->canAccessCurrentCompany($user)
            : $this->belongsToCurrentCompany($user, $employee);
    }

    private function belongsToCurrentCompany(User $user, Employee $employee): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company
            && $user->canAccessTenant($company)
            && $employee->isEmployedBy($company);
    }

    private function canAccessCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company && $user->canAccessTenant($company);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
