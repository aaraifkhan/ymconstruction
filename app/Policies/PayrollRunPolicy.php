<?php

namespace App\Policies;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Filament\Facades\Filament;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:PayrollRun') && $this->hasTenant($user);
    }

    public function view(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'View:PayrollRun') && $user->canAccessTenant($run->company);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:PayrollRun') && $this->hasTenant($user);
    }

    public function update(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Update:PayrollRun') && $user->canAccessTenant($run->company) && in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true);
    }

    public function delete(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Delete:PayrollRun') && $user->canAccessTenant($run->company) && in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true);
    }

    public function restore(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Restore:PayrollRun') && $user->canAccessTenant($run->company);
    }

    public function viewAmounts(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'ViewAmounts:PayrollRun') && $user->canAccessTenant($run->company);
    }

    public function generateEntries(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'GenerateEntries:PayrollRun') && $user->canAccessTenant($run->company) && in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true);
    }

    public function submit(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Submit:PayrollRun') && $user->canAccessTenant($run->company) && in_array($run->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true);
    }

    public function approve(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Approve:PayrollRun') && $user->canAccessTenant($run->company) && $run->status === PayrollRunStatus::UnderReview;
    }

    public function reject(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Reject:PayrollRun') && $user->canAccessTenant($run->company) && $run->status === PayrollRunStatus::UnderReview;
    }

    public function markPaid(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'MarkPaid:PayrollRun') && $user->canAccessTenant($run->company) && $run->status === PayrollRunStatus::Approved;
    }

    public function post(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Post:PayrollRun') && $user->canAccessTenant($run->company)
            && $run->status === PayrollRunStatus::Approved && ! $run->isPostedToAccounts();
    }

    public function reverse(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Reverse:PayrollRun') && $user->canAccessTenant($run->company)
            && $run->status === PayrollRunStatus::Approved && $run->isPostedToAccounts();
    }

    public function lock(User $user, PayrollRun $run): bool
    {
        return $this->allowed($user, 'Lock:PayrollRun') && $user->canAccessTenant($run->company) && $run->status === PayrollRunStatus::Paid;
    }

    public function forceDelete(User $user, PayrollRun $run): bool
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

    private function hasTenant(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
