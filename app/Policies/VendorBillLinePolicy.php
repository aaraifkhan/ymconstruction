<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VendorBillLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'VendorBillLine';

    public function update(User $user, Model $line): bool
    {
        return parent::update($user, $line) && $line->vendorBill()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $line): bool
    {
        return parent::delete($user, $line) && $line->vendorBill()->first()?->isEditable() === true;
    }
}
