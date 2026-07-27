<?php

namespace App\Actions\AccountsPayable;

use App\Enums\GoodsReceiptStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\GoodsReceiptLine;
use App\Models\VendorBill;
use App\Models\VendorBillReceiptAllocation;
use Illuminate\Validation\ValidationException;

class AllocateVendorBillReceiptsAction
{
    public function handle(VendorBill $bill): void
    {
        if ($bill->type !== VendorBillType::Invoice) {
            return;
        }

        foreach ($bill->lines()->with('item')->orderBy('line_number')->lockForUpdate()->get() as $line) {
            $line->allocations()->delete();
            if (! $line->item?->track_inventory) {
                continue;
            }

            $remaining = (string) $line->quantity;
            $receiptLines = GoodsReceiptLine::query()
                ->where('company_id', $bill->company_id)
                ->where('purchase_order_line_id', $line->purchase_order_line_id)
                ->whereHas('goodsReceipt', fn ($query) => $query
                    ->where('vendor_id', $bill->vendor_id)
                    ->where('status', GoodsReceiptStatus::HandedOver))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($receiptLines as $receiptLine) {
                $allocated = (string) VendorBillReceiptAllocation::query()
                    ->where('goods_receipt_line_id', $receiptLine->getKey())
                    ->whereHas('vendorBillLine.vendorBill', fn ($query) => $query
                        ->where('type', VendorBillType::Invoice->value)
                        ->whereNotIn('status', [
                            VendorBillStatus::Draft->value,
                            VendorBillStatus::Rejected->value,
                            VendorBillStatus::Reversed->value,
                        ]))
                    ->sum('quantity');
                $available = bcsub(
                    bcsub((string) $receiptLine->accepted_quantity, (string) $receiptLine->accepted_returned_quantity, 4),
                    $allocated,
                    4,
                );
                if (bccomp($available, '0', 4) !== 1) {
                    continue;
                }

                $quantity = bccomp($available, $remaining, 4) === 1 ? $remaining : $available;
                $line->allocations()->create([
                    'company_id' => $bill->company_id,
                    'goods_receipt_line_id' => $receiptLine->getKey(),
                    'quantity' => $quantity,
                    'receipt_unit_cost' => $receiptLine->unit_cost_snapshot,
                    'receipt_value' => bcmul($quantity, (string) $receiptLine->unit_cost_snapshot, 4),
                ]);
                $remaining = bcsub($remaining, $quantity, 4);
                if (bccomp($remaining, '0', 4) === 0) {
                    break;
                }
            }

            if (bccomp($remaining, '0', 4) === 1) {
                throw ValidationException::withMessages([
                    'lines' => "Line {$line->line_number} exceeds handed-over accepted quantity available for invoicing.",
                ]);
            }
        }
    }
}
