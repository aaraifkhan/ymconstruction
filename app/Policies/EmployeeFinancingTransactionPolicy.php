<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeFinancingTransactionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeFinancingTransaction';

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
