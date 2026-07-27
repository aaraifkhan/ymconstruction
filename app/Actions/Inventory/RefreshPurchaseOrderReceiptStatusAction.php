<?php

namespace App\Actions\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;

class RefreshPurchaseOrderReceiptStatusAction
{
    public function handle(int $purchaseOrderId): PurchaseOrder
    {
        $order = PurchaseOrder::query()->whereKey($purchaseOrderId)->lockForUpdate()->firstOrFail();
        $lines = $order->lines()->lockForUpdate()->get();
        $fullyReceived = $lines->every(
            fn (PurchaseOrderLine $line): bool => bccomp($line->availableToReceive(), '0', 4) === 0,
        );
        $hasReceipt = $lines->contains(
            fn (PurchaseOrderLine $line): bool => bccomp((string) $line->received_quantity, '0', 4) === 1,
        );

        $order->update([
            'status' => match (true) {
                $fullyReceived => PurchaseOrderStatus::Received,
                $hasReceipt => PurchaseOrderStatus::PartiallyReceived,
                default => PurchaseOrderStatus::Ordered,
            },
        ]);

        return $order->refresh();
    }
}
