<?php

namespace App\Policies;

use App\Models\DocumentCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:DocumentCategory')
            && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, DocumentCategory $documentCategory): bool
    {
        return $this->hasPermission($user, 'View:DocumentCategory')
            && $user->canAccessTenant($documentCategory->company);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:DocumentCategory')
            && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, DocumentCategory $documentCategory): bool
    {
        return $this->hasPermission($user, 'Update:DocumentCategory')
            && $user->canAccessTenant($documentCategory->company);
    }

    public function delete(User $user, DocumentCategory $documentCategory): bool
    {
        return $this->hasPermission($user, 'Delete:DocumentCategory')
            && $user->canAccessTenant($documentCategory->company)
            && ! $documentCategory->documents()->exists();
    }

    public function restore(User $user, DocumentCategory $documentCategory): bool
    {
        return $this->hasPermission($user, 'Restore:DocumentCategory')
            && $user->canAccessTenant($documentCategory->company);
    }

    public function forceDelete(User $user, DocumentCategory $documentCategory): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:DocumentCategory')
            && $this->canAccessCurrentCompany($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:DocumentCategory')
            && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canAccessCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
