<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PurchaseRequisitionLine';

    public function update(User $user, Model $line): bool
    {
        return parent::update($user, $line) && $line->requisition()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $line): bool
    {
        return parent::delete($user, $line) && $line->requisition()->first()?->isEditable() === true;
    }
}
