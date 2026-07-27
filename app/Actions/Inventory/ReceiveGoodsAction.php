<?php

namespace App\Actions\Inventory;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReceiveGoodsAction
{
    public function __construct(
        private ReserveGoodsReceiptNumberAction $reserveNumber,
        private RefreshPurchaseOrderReceiptStatusAction $refreshOrderStatus,
    ) {}

    public function handle(GoodsReceipt $receipt, User $actor): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('receive', $receipt);

        return DB::transaction(function () use ($actor, $receipt): GoodsReceipt {
            $receipt = GoodsReceipt::query()->whereKey($receipt)->lockForUpdate()->firstOrFail();
            if ($receipt->status !== GoodsReceiptStatus::Draft) {
                return $receipt;
            }

            $order = PurchaseOrder::query()->whereKey($receipt->purchase_order_id)->lockForUpdate()->firstOrFail();
            if (! in_array($order->status, [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw ValidationException::withMessages(['purchase_order_id' => 'Only an issued order with remaining quantity can be received.']);
            }

            $receiptLines = $receipt->lines()->orderBy('purchase_order_line_id')->lockForUpdate()->get();
            if ($receiptLines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'A Goods Receipt requires at least one line.']);
            }

            foreach ($receiptLines as $receiptLine) {
                $orderLine = PurchaseOrderLine::query()
                    ->whereKey($receiptLine->purchase_order_line_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $orderLine->item()->where('track_inventory', true)->exists()) {
                    throw ValidationException::withMessages(['item_id' => 'Goods Receipts only accept stock-tracked material items.']);
                }

                if (bccomp((string) $receiptLine->received_quantity, $orderLine->availableToReceive(), 4) === 1) {
                    throw ValidationException::withMessages([
                        'received_quantity' => "Line {$receiptLine->line_number} exceeds the purchase-order quantity available to receive.",
                    ]);
                }

                $orderLine->update([
                    'received_quantity' => bcadd(
                        (string) $orderLine->received_quantity,
                        (string) $receiptLine->received_quantity,
                        4,
                    ),
                ]);
            }

            $receipt->update([
                'goods_receipt_number' => $this->reserveNumber->handle(
                    $receipt->company,
                    $receipt->delivery_date->year,
                ),
                'status' => GoodsReceiptStatus::Received,
                'received_by_id' => $actor->getKey(),
                'received_at' => now(),
            ]);

            $this->refreshOrderStatus->handle($order->getKey());

            activity('goods_receipts')->causedBy($actor)->performedOn($receipt)->event('received')
                ->withProperties([
                    'company_id' => $receipt->company_id,
                    'purchase_order_id' => $receipt->purchase_order_id,
                    'line_count' => $receiptLines->count(),
                ])->log('recorded vendor delivery');

            return $receipt->refresh();
        }, attempts: 3);
    }
}
