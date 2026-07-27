<?php

namespace App\Policies;

use App\Enums\InventoryTransactionStatus;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'InventoryTransaction';

    public function update(User $user, Model $transaction): bool
    {
        return parent::update($user, $transaction) && $transaction->isEditable();
    }

    public function delete(User $user, Model $transaction): bool
    {
        return parent::delete($user, $transaction)
            && $transaction->status === InventoryTransactionStatus::Draft;
    }

    public function post(User $user, InventoryTransaction $transaction): bool
    {
        return $this->hasPermission($user, 'Post:InventoryTransaction')
            && $this->canAccessRecord($user, $transaction)
            && $transaction->status === InventoryTransactionStatus::Draft;
    }
}
