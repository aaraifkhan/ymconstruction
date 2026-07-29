<?php

namespace App\Policies;

use App\Models\HrDataMigration;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrDataMigrationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'HrDataMigration';

    public function validate(User $user, HrDataMigration $migration): bool
    {
        return $this->hasPermission($user, 'Validate:HrDataMigration')
            && $this->canAccessRecord($user, $migration);
    }

    public function import(User $user, HrDataMigration $migration): bool
    {
        return $this->hasPermission($user, 'Import:HrDataMigration')
            && $this->canAccessRecord($user, $migration);
    }

    public function rollback(User $user, HrDataMigration $migration): bool
    {
        return $this->hasPermission($user, 'Rollback:HrDataMigration')
            && $this->canAccessRecord($user, $migration);
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }
}
