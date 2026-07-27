<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VendorBillDeductionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'VendorBillDeduction';

    public function update(User $user, Model $deduction): bool
    {
        return parent::update($user, $deduction)
            && $deduction->vendorBill()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $deduction): bool
    {
        return parent::delete($user, $deduction)
            && $deduction->vendorBill()->first()?->isEditable() === true;
    }
}
