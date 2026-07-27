<?php

namespace App\Policies;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'GoodsReceipt';

    public function update(User $user, Model $receipt): bool
    {
        return parent::update($user, $receipt) && $receipt->isEditable();
    }

    public function delete(User $user, Model $receipt): bool
    {
        return parent::delete($user, $receipt) && $receipt->status === GoodsReceiptStatus::Draft;
    }

    public function receive(User $user, GoodsReceipt $receipt): bool
    {
        return $this->hasPermission($user, 'Receive:GoodsReceipt')
            && $this->canAccessRecord($user, $receipt)
            && $receipt->status === GoodsReceiptStatus::Draft;
    }

    public function inspect(User $user, GoodsReceipt $receipt): bool
    {
        return $this->hasPermission($user, 'Inspect:GoodsReceipt')
            && $this->canAccessRecord($user, $receipt)
            && $receipt->status === GoodsReceiptStatus::Received;
    }

    public function handover(User $user, GoodsReceipt $receipt): bool
    {
        return $this->hasPermission($user, 'Handover:GoodsReceipt')
            && $this->canAccessRecord($user, $receipt)
            && $receipt->status === GoodsReceiptStatus::Inspected;
    }
}
