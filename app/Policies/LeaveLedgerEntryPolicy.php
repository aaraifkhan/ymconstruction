<?php

namespace App\Policies;

use App\Models\Employment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LeaveLedgerEntryPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'LeaveLedgerEntry';

    public function adjust(User $user, Employment $employment): bool
    {
        return $this->hasPermission($user, 'Adjust:LeaveLedgerEntry') && $this->canAccessRecord($user, $employment);
    }

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
