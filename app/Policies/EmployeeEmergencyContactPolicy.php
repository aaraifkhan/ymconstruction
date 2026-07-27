<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\EmployeeEmergencyContact;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeEmergencyContactPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:EmployeeEmergencyContact') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, EmployeeEmergencyContact $contact): bool
    {
        return $this->allowed($user, 'View:EmployeeEmergencyContact') && $this->belongsToCurrentCompany($user, $contact);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:EmployeeEmergencyContact') && $this->hasCurrentCompany($user);
    }

    public function update(User $user, EmployeeEmergencyContact $contact): bool
    {
        return $this->allowed($user, 'Update:EmployeeEmergencyContact') && $this->belongsToCurrentCompany($user, $contact);
    }

    public function delete(User $user, EmployeeEmergencyContact $contact): bool
    {
        return $this->allowed($user, 'Delete:EmployeeEmergencyContact') && $this->belongsToCurrentCompany($user, $contact);
    }

    public function restore(User $user, EmployeeEmergencyContact $contact): bool
    {
        return $this->allowed($user, 'Restore:EmployeeEmergencyContact') && $this->belongsToCurrentCompany($user, $contact);
    }

    public function forceDelete(User $user, EmployeeEmergencyContact $contact): bool
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

    private function belongsToCurrentCompany(User $user, EmployeeEmergencyContact $contact): bool
    {
        $company = Filament::getTenant();

        return $company instanceof Company
            && $user->canAccessTenant($company)
            && $contact->employee->isEmployedBy($company);
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
