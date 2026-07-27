<?php

namespace App\Policies;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class JoiningLetterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:JoiningLetter') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'View:JoiningLetter') && $user->canAccessTenant($letter->company);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:JoiningLetter') && $this->hasCurrentCompany($user);
    }

    public function update(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Update:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && in_array($letter->status, [JoiningLetterStatus::Draft, JoiningLetterStatus::Rejected], true);
    }

    public function delete(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Delete:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && in_array($letter->status, [JoiningLetterStatus::Draft, JoiningLetterStatus::Rejected], true);
    }

    public function restore(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Restore:JoiningLetter') && $user->canAccessTenant($letter->company);
    }

    public function viewSensitive(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'ViewSensitive:JoiningLetter') && $user->canAccessTenant($letter->company);
    }

    public function viewCompensation(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'ViewCompensation:JoiningLetter') && $user->canAccessTenant($letter->company);
    }

    public function manageCompensation(User $user, ?JoiningLetter $letter = null): bool
    {
        if (! $this->allowed($user, 'ManageCompensation:JoiningLetter')) {
            return false;
        }

        return $letter === null
            ? $this->hasCurrentCompany($user)
            : $user->canAccessTenant($letter->company)
                && in_array($letter->status, [JoiningLetterStatus::Draft, JoiningLetterStatus::Rejected], true);
    }

    public function regenerate(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Regenerate:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && in_array($letter->status, [JoiningLetterStatus::Draft, JoiningLetterStatus::Rejected], true);
    }

    public function submit(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Submit:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && $letter->status === JoiningLetterStatus::Draft;
    }

    public function approve(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Approve:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && $letter->status === JoiningLetterStatus::PendingApproval;
    }

    public function reject(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Reject:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && $letter->status === JoiningLetterStatus::PendingApproval;
    }

    public function issue(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'Issue:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && $letter->status === JoiningLetterStatus::Approved;
    }

    public function recordAcceptance(User $user, JoiningLetter $letter): bool
    {
        return $this->allowed($user, 'RecordAcceptance:JoiningLetter')
            && $user->canAccessTenant($letter->company)
            && $letter->status === JoiningLetterStatus::Issued;
    }

    public function forceDelete(User $user, JoiningLetter $letter): bool
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
