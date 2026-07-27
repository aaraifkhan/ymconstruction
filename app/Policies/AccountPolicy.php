<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccountPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'Account';

    public function delete(User $user, Model $account): bool
    {
        return $account instanceof Account
            && parent::delete($user, $account)
            && ! $account->children()->exists()
            && ! $account->mappings()->exists()
            && ! $account->journalLines()->exists();
    }
}
