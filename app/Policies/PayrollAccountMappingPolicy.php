<?php

namespace App\Policies;

use App\Models\PayrollAccountMapping;
use App\Models\User;
use Filament\Facades\Filament;

class PayrollAccountMappingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny:PayrollAccountMapping')
            && Filament::getTenant() !== null && $user->canAccessTenant(Filament::getTenant());
    }

    public function view(User $user, PayrollAccountMapping $mapping): bool
    {
        return $this->allowed($user, 'View:PayrollAccountMapping') && $user->canAccessTenant($mapping->company);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create:PayrollAccountMapping')
            && Filament::getTenant() !== null && $user->canAccessTenant(Filament::getTenant());
    }

    public function update(User $user, PayrollAccountMapping $mapping): bool
    {
        return $this->allowed($user, 'Update:PayrollAccountMapping') && $user->canAccessTenant($mapping->company);
    }

    public function delete(User $user, PayrollAccountMapping $mapping): bool
    {
        return $this->allowed($user, 'Delete:PayrollAccountMapping') && $user->canAccessTenant($mapping->company);
    }

    public function restore(User $user, PayrollAccountMapping $mapping): bool
    {
        return false;
    }

    public function forceDelete(User $user, PayrollAccountMapping $mapping): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
