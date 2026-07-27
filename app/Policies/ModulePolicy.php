<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Module');
    }

    public function view(User $user, Module $module): bool
    {
        return $this->hasPermission($user, 'View:Module');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Module');
    }

    public function update(User $user, Module $module): bool
    {
        return $this->hasPermission($user, 'Update:Module');
    }

    public function delete(User $user, Module $module): bool
    {
        return $this->hasPermission($user, 'Delete:Module');
    }

    public function restore(User $user, Module $module): bool
    {
        return false;
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:Module');
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
