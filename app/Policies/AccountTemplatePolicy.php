<?php

namespace App\Policies;

use App\Models\AccountTemplate;
use App\Models\User;

class AccountTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny');
    }

    public function view(User $user, AccountTemplate $template): bool
    {
        return $this->allowed($user, 'View');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create');
    }

    public function update(User $user, AccountTemplate $template): bool
    {
        return $this->allowed($user, 'Update');
    }

    public function delete(User $user, AccountTemplate $template): bool
    {
        return $this->allowed($user, 'Delete') && ! $template->children()->exists() && ! $template->accounts()->exists();
    }

    public function restore(User $user, AccountTemplate $template): bool
    {
        return $this->allowed($user, 'Restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowed($user, 'RestoreAny');
    }

    public function forceDelete(User $user, AccountTemplate $template): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function allowed(User $user, string $ability): bool
    {
        return $user->hasRole('super_admin') || $user->can("{$ability}:AccountTemplate");
    }
}
