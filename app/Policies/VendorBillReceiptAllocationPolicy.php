<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VendorBillReceiptAllocationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'VendorBillReceiptAllocation';

    public function update(User $user, Model $allocation): bool
    {
        return parent::update($user, $allocation)
            && $allocation->vendorBillLine()->first()?->vendorBill()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $allocation): bool
    {
        return parent::delete($user, $allocation)
            && $allocation->vendorBillLine()->first()?->vendorBill()->first()?->isEditable() === true;
    }
}
