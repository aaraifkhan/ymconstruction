<?php

namespace App\Policies;

use App\Models\GoodsReceiptLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'GoodsReceiptLine';

    public function update(User $user, Model $line): bool
    {
        return parent::update($user, $line)
            && $line->goodsReceipt()->first()?->isEditable() === true;
    }

    public function delete(User $user, Model $line): bool
    {
        return parent::delete($user, $line)
            && $line->goodsReceipt()->first()?->isEditable() === true;
    }

    public function returnRejected(User $user, GoodsReceiptLine $line): bool
    {
        return $this->hasPermission($user, 'ReturnRejected:GoodsReceipt')
            && $this->canAccessRecord($user, $line);
    }
}
