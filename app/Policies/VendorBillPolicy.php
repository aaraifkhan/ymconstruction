<?php

namespace App\Policies;

use App\Enums\VendorBillStatus;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Model;

class VendorBillPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'VendorBill';

    public function update(User $user, Model $bill): bool
    {
        return parent::update($user, $bill) && $bill->isEditable();
    }

    public function delete(User $user, Model $bill): bool
    {
        return parent::delete($user, $bill) && $bill->isEditable();
    }

    public function submit(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'Submit:VendorBill')
            && $bill->isEditable();
    }

    public function reviewMatch(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'ReviewMatch:VendorBill')
            && $bill->status === VendorBillStatus::Submitted;
    }

    public function overrideMatch(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'OverrideMatch:VendorBill')
            && $bill->status === VendorBillStatus::Submitted;
    }

    public function approve(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'Approve:VendorBill')
            && $bill->status === VendorBillStatus::Reviewed;
    }

    public function reject(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'Reject:VendorBill')
            && in_array($bill->status, [VendorBillStatus::Submitted, VendorBillStatus::Reviewed], true);
    }

    public function post(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'Post:VendorBill')
            && $bill->status === VendorBillStatus::Approved;
    }

    public function reverse(User $user, VendorBill $bill): bool
    {
        return $this->canPerform($user, $bill, 'Reverse:VendorBill')
            && $bill->status === VendorBillStatus::Posted;
    }

    private function canPerform(User $user, VendorBill $bill, string $permission): bool
    {
        return $this->hasPermission($user, $permission) && $this->canAccessRecord($user, $bill);
    }
}
