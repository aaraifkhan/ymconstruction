<?php

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PurchaseOrder';

    public function update(User $user, Model $order): bool
    {
        return parent::update($user, $order) && $order->isEditable();
    }

    public function delete(User $user, Model $order): bool
    {
        return parent::delete($user, $order)
            && $order->status === PurchaseOrderStatus::Draft;
    }

    public function submit(User $user, PurchaseOrder $order): bool
    {
        return $this->hasPermission($user, 'Submit:PurchaseOrder')
            && $this->canAccessRecord($user, $order)
            && $order->isEditable();
    }

    public function approve(User $user, PurchaseOrder $order): bool
    {
        return $this->hasPermission($user, 'Approve:PurchaseOrder')
            && $this->canAccessRecord($user, $order)
            && $order->status === PurchaseOrderStatus::Submitted;
    }

    public function reject(User $user, PurchaseOrder $order): bool
    {
        return $this->hasPermission($user, 'Reject:PurchaseOrder')
            && $this->canAccessRecord($user, $order)
            && $order->status === PurchaseOrderStatus::Submitted;
    }

    public function issue(User $user, PurchaseOrder $order): bool
    {
        return $this->hasPermission($user, 'Issue:PurchaseOrder')
            && $this->canAccessRecord($user, $order)
            && $order->status === PurchaseOrderStatus::Approved;
    }

    public function cancel(User $user, PurchaseOrder $order): bool
    {
        return $this->hasPermission($user, 'Cancel:PurchaseOrder')
            && $this->canAccessRecord($user, $order)
            && in_array($order->status, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::Rejected,
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Ordered,
            ], true);
    }
}
