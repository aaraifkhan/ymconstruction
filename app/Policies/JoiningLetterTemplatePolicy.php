<?php

namespace App\Policies;

use App\Models\JoiningLetterTemplate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class JoiningLetterTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:JoiningLetterTemplate') && $this->hasCurrentCompany($user);
    }

    public function view(User $user, JoiningLetterTemplate $template): bool
    {
        return $this->allowed($user, 'View:JoiningLetterTemplate') && $user->canAccessTenant($template->company);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:JoiningLetterTemplate') && $this->hasCurrentCompany($user);
    }

    public function update(User $user, JoiningLetterTemplate $template): bool
    {
        return $this->allowed($user, 'Update:JoiningLetterTemplate') && $user->canAccessTenant($template->company);
    }

    public function delete(User $user, JoiningLetterTemplate $template): bool
    {
        return $this->allowed($user, 'Delete:JoiningLetterTemplate')
            && $user->canAccessTenant($template->company)
            && ! $template->joiningLetters()->exists();
    }

    public function restore(User $user, JoiningLetterTemplate $template): bool
    {
        return $this->allowed($user, 'Restore:JoiningLetterTemplate') && $user->canAccessTenant($template->company);
    }

    public function forceDelete(User $user, JoiningLetterTemplate $template): bool
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
