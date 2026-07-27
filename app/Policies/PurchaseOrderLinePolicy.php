<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PurchaseOrderLine';

    public function update(User $user, Model $line): bool
    {
        return parent::update($user, $line) && $line->purchaseOrder()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $line): bool
    {
        return parent::delete($user, $line) && $line->purchaseOrder()->first()?->isEditable() === true;
    }
}
