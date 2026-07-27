<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\EmployeeExperience;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeExperiencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:EmployeeExperience') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, EmployeeExperience $experience): bool
    {
        return $this->allowed($user, 'View:EmployeeExperience') && $this->belongsToCurrentCompany($user, $experience);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:EmployeeExperience') && $this->hasCurrentCompany($user);
    }

    public function update(User $user, EmployeeExperience $experience): bool
    {
        return $this->allowed($user, 'Update:EmployeeExperience') && $this->belongsToCurrentCompany($user, $experience);
    }

    public function delete(User $user, EmployeeExperience $experience): bool
    {
        return $this->allowed($user, 'Delete:EmployeeExperience') && $this->belongsToCurrentCompany($user, $experience);
    }

    public function restore(User $user, EmployeeExperience $experience): bool
    {
        return $this->allowed($user, 'Restore:EmployeeExperience') && $this->belongsToCurrentCompany($user, $experience);
    }

    public function forceDelete(User $user, EmployeeExperience $experience): bool
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

    private function belongsToCurrentCompany(User $user, EmployeeExperience $experience): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company
            && $user->canAccessTenant($company)
            && $experience->employee->isEmployedBy($company);
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
