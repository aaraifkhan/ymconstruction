<?php

namespace App\Policies;

use App\Models\OpeningBalanceMigration;
use App\Models\User;

class OpeningBalanceMigrationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'OpeningBalanceMigration';

    public function update(User $user, mixed $record): bool
    {
        return false;
    }

    public function delete(User $user, mixed $record): bool
    {
        return false;
    }

    public function validate(User $user, OpeningBalanceMigration $migration): bool
    {
        return $this->workflow($user, $migration, 'Validate');
    }

    public function import(User $user, OpeningBalanceMigration $migration): bool
    {
        return $this->workflow($user, $migration, 'Import');
    }

    public function reverse(User $user, OpeningBalanceMigration $migration): bool
    {
        return $this->workflow($user, $migration, 'Reverse');
    }

    private function workflow(User $user, OpeningBalanceMigration $migration, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:OpeningBalanceMigration")
            && $this->canAccessRecord($user, $migration);
    }
}
