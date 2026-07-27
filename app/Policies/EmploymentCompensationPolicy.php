<?php

namespace App\Policies;

use App\Enums\CompensationStatus;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmploymentCompensationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:EmploymentCompensation') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'View:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:EmploymentCompensation')
            && $this->allowed($user, 'ManageAmounts:EmploymentCompensation')
            && $this->hasCurrentCompany($user);
    }

    public function update(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'Update:EmploymentCompensation')
            && $this->allowed($user, 'ManageAmounts:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company)
            && in_array($compensation->status, [CompensationStatus::Draft, CompensationStatus::Rejected], true);
    }

    public function delete(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'Delete:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company)
            && in_array($compensation->status, [CompensationStatus::Draft, CompensationStatus::Rejected], true);
    }

    public function restore(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'Restore:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company);
    }

    public function viewAmounts(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'ViewAmounts:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company);
    }

    public function manageAmounts(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'ManageAmounts:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company)
            && in_array($compensation->status, [CompensationStatus::Draft, CompensationStatus::Rejected], true);
    }

    public function submit(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'Submit:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company)
            && in_array($compensation->status, [CompensationStatus::Draft, CompensationStatus::Rejected], true);
    }

    public function approve(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'Approve:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company)
            && $compensation->status === CompensationStatus::PendingApproval;
    }

    public function reject(User $user, EmploymentCompensation $compensation): bool
    {
        return $this->allowed($user, 'Reject:EmploymentCompensation')
            && $user->canAccessTenant($compensation->company)
            && $compensation->status === CompensationStatus::PendingApproval;
    }

    public function forceDelete(User $user, EmploymentCompensation $compensation): bool
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

    private function hasCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
