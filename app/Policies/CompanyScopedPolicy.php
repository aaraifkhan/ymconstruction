<?php

namespace App\Policies;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

abstract class CompanyScopedPolicy
{
    protected string $permissionSubject;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, "ViewAny:{$this->permissionSubject}")
            && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->hasPermission($user, "View:{$this->permissionSubject}")
            && $this->canAccessRecord($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, "Create:{$this->permissionSubject}")
            && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->hasPermission($user, "Update:{$this->permissionSubject}")
            && $this->canAccessRecord($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->hasPermission($user, "Delete:{$this->permissionSubject}")
            && $this->canAccessRecord($user, $record);
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->hasPermission($user, "Restore:{$this->permissionSubject}")
            && $this->canAccessRecord($user, $record);
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, "RestoreAny:{$this->permissionSubject}")
            && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    protected function canAccessRecord(User $user, Model $record): bool
    {
        $company = $record->getRelationValue('company');

        return $company !== null && $user->canAccessTenant($company);
    }

    protected function canAccessCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
