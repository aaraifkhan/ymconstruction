<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\EmployeeQualification;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeQualificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:EmployeeQualification') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, EmployeeQualification $qualification): bool
    {
        return $this->allowed($user, 'View:EmployeeQualification') && $this->belongsToCurrentCompany($user, $qualification);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:EmployeeQualification') && $this->hasCurrentCompany($user);
    }

    public function update(User $user, EmployeeQualification $qualification): bool
    {
        return $this->allowed($user, 'Update:EmployeeQualification') && $this->belongsToCurrentCompany($user, $qualification);
    }

    public function delete(User $user, EmployeeQualification $qualification): bool
    {
        return $this->allowed($user, 'Delete:EmployeeQualification') && $this->belongsToCurrentCompany($user, $qualification);
    }

    public function restore(User $user, EmployeeQualification $qualification): bool
    {
        return $this->allowed($user, 'Restore:EmployeeQualification') && $this->belongsToCurrentCompany($user, $qualification);
    }

    public function forceDelete(User $user, EmployeeQualification $qualification): bool
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

    private function belongsToCurrentCompany(User $user, EmployeeQualification $qualification): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company
            && $user->canAccessTenant($company)
            && $qualification->employee->isEmployedBy($company);
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
