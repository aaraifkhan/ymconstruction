<?php

namespace App\Models;

use Database\Factories\VendorBillReceiptAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'vendor_bill_line_id', 'goods_receipt_line_id', 'company_id', 'quantity',
    'receipt_unit_cost', 'receipt_value',
])]
class VendorBillReceiptAllocation extends Model
{
    /** @use HasFactory<VendorBillReceiptAllocationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $allocation): void {
            $billLine = VendorBillLine::query()->with('vendorBill')->find($allocation->vendor_bill_line_id);
            $receiptLine = GoodsReceiptLine::query()->with('goodsReceipt')->find($allocation->goods_receipt_line_id);
            if ($billLine === null || $receiptLine === null
                || (int) $billLine->company_id !== (int) $allocation->company_id
                || (int) $receiptLine->company_id !== (int) $allocation->company_id
                || (int) $billLine->purchase_order_line_id !== (int) $receiptLine->purchase_order_line_id
                || (int) $billLine->vendorBill->vendor_id !== (int) $receiptLine->goodsReceipt->vendor_id) {
                throw ValidationException::withMessages(['goods_receipt_line_id' => 'Allocation must use a same-company receipt line for the same PO line and vendor.']);
            }
            if (! $billLine->vendorBill->isEditable()) {
                throw ValidationException::withMessages(['vendor_bill_line_id' => 'Submitted Vendor Bill allocations are immutable.']);
            }
            if (bccomp((string) $allocation->quantity, '0', 4) !== 1) {
                throw ValidationException::withMessages(['quantity' => 'Allocated quantity must be positive.']);
            }
            $allocation->receipt_unit_cost = $receiptLine->unit_cost_snapshot;
            $allocation->receipt_value = bcmul((string) $allocation->quantity, (string) $receiptLine->unit_cost_snapshot, 4);
        });

        static::deleting(function (self $allocation): void {
            if (! $allocation->vendorBillLine()->firstOrFail()->vendorBill()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['vendor_bill_line_id' => 'Submitted receipt allocations are immutable.']);
            }
        });
    }

    public function vendorBillLine(): BelongsTo
    {
        return $this->belongsTo(VendorBillLine::class);
    }

    public function goodsReceiptLine(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptLine::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'receipt_unit_cost' => 'decimal:4',
            'receipt_value' => 'decimal:4',
        ];
    }
}
