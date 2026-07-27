<?php

namespace App\Actions\Inventory;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceiptLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordRejectedGoodsReturnAction
{
    public function __construct(private RefreshPurchaseOrderReceiptStatusAction $refreshOrderStatus) {}

    public function handle(GoodsReceiptLine $line, string $quantity, User $actor): GoodsReceiptLine
    {
        Gate::forUser($actor)->authorize('returnRejected', $line);

        return DB::transaction(function () use ($actor, $line, $quantity): GoodsReceiptLine {
            $line = GoodsReceiptLine::query()->whereKey($line)->lockForUpdate()->firstOrFail();
            $receipt = $line->goodsReceipt()->lockForUpdate()->firstOrFail();
            if (! in_array($receipt->status, [GoodsReceiptStatus::Inspected, GoodsReceiptStatus::HandedOver], true)) {
                throw ValidationException::withMessages(['status' => 'Rejected material may be returned only after inspection.']);
            }
            if (bccomp($quantity, '0', 4) !== 1 || bccomp($quantity, $line->availableRejectedToReturn(), 4) === 1) {
                throw ValidationException::withMessages(['quantity' => 'Return quantity exceeds rejected material awaiting return.']);
            }

            $line->update([
                'rejected_returned_quantity' => bcadd(
                    (string) $line->rejected_returned_quantity,
                    $quantity,
                    4,
                ),
            ]);
            $orderLine = $line->purchaseOrderLine()->lockForUpdate()->firstOrFail();
            $orderLine->update([
                'received_quantity' => bcsub((string) $orderLine->received_quantity, $quantity, 4),
            ]);
            $this->refreshOrderStatus->handle($orderLine->purchase_order_id);

            activity('goods_receipts')->causedBy($actor)->performedOn($receipt)->event('rejected_goods_returned')
                ->withProperties([
                    'company_id' => $receipt->company_id,
                    'goods_receipt_line_id' => $line->getKey(),
                    'quantity' => $quantity,
                ])->log('returned rejected material to vendor');

            return $line->refresh();
        });
    }
}
