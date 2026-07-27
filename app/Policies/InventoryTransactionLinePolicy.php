<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'InventoryTransactionLine';

    public function update(User $user, Model $line): bool
    {
        return parent::update($user, $line)
            && $line->inventoryTransaction()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $line): bool
    {
        return parent::delete($user, $line)
            && $line->inventoryTransaction()->first()?->isEditable() === true;
    }
}
