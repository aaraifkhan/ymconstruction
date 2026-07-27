<?php

namespace App\Policies;

use App\Enums\BankStatementStatus;
use App\Models\BankStatement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BankStatementPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'BankStatement';

    public function update(User $user, Model $statement): bool
    {
        return parent::update($user, $statement) && $statement->status === BankStatementStatus::Draft;
    }

    public function delete(User $user, Model $statement): bool
    {
        return parent::delete($user, $statement) && $statement->status === BankStatementStatus::Draft;
    }

    public function import(User $user, BankStatement $statement): bool
    {
        return $this->hasPermission($user, 'Import:BankStatement')
            && $this->canAccessRecord($user, $statement)
            && $statement->status === BankStatementStatus::Draft;
    }
}
