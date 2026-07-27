<?php

namespace App\Policies;

use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PurchaseRequisition';

    public function update(User $user, Model $requisition): bool
    {
        return parent::update($user, $requisition) && $requisition->isEditable();
    }

    public function delete(User $user, Model $requisition): bool
    {
        return parent::delete($user, $requisition)
            && $requisition->status === PurchaseRequisitionStatus::Draft;
    }

    public function submit(User $user, PurchaseRequisition $requisition): bool
    {
        return $this->hasPermission($user, 'Submit:PurchaseRequisition')
            && $this->canAccessRecord($user, $requisition)
            && $requisition->isEditable();
    }

    public function approve(User $user, PurchaseRequisition $requisition): bool
    {
        return $this->hasPermission($user, 'Approve:PurchaseRequisition')
            && $this->canAccessRecord($user, $requisition)
            && $requisition->status === PurchaseRequisitionStatus::Submitted;
    }

    public function reject(User $user, PurchaseRequisition $requisition): bool
    {
        return $this->hasPermission($user, 'Reject:PurchaseRequisition')
            && $this->canAccessRecord($user, $requisition)
            && $requisition->status === PurchaseRequisitionStatus::Submitted;
    }

    public function cancel(User $user, PurchaseRequisition $requisition): bool
    {
        return $this->hasPermission($user, 'Cancel:PurchaseRequisition')
            && $this->canAccessRecord($user, $requisition)
            && in_array($requisition->status, [
                PurchaseRequisitionStatus::Draft,
                PurchaseRequisitionStatus::Rejected,
                PurchaseRequisitionStatus::Approved,
            ], true);
    }
}
