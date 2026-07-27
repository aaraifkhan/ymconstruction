<?php

namespace App\Policies;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollEntry;
use App\Models\User;

class PayrollEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:PayrollEntry');
    }

    public function view(User $user, PayrollEntry $entry): bool
    {
        return $this->allowed($user, 'View:PayrollEntry') && $user->canAccessTenant($entry->company);
    }

    public function update(User $user, PayrollEntry $entry): bool
    {
        return $this->allowed($user, 'Update:PayrollEntry') && $user->canAccessTenant($entry->company) && in_array($entry->payrollRun->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true);
    }

    public function viewAmounts(User $user, PayrollEntry $entry): bool
    {
        return $this->allowed($user, 'ViewAmounts:PayrollEntry') && $user->canAccessTenant($entry->company);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, PayrollEntry $entry): bool
    {
        return false;
    }

    public function restore(User $user, PayrollEntry $entry): bool
    {
        return false;
    }

    public function forceDelete(User $user, PayrollEntry $entry): bool
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

    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
