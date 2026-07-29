<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeAssetCustodyEventPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeAssetCustodyEvent';

    public function create(User $user): bool
    {
        return false;
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
