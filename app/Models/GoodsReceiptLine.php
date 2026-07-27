<?php

namespace App\Models;

use App\Enums\InspectionResult;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use Database\Factories\GoodsReceiptLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'goods_receipt_id', 'company_id', 'purchase_order_line_id', 'line_number',
    'item_id', 'unit_of_measure_id', 'item_code_snapshot', 'item_name_snapshot',
    'uom_snapshot', 'received_quantity', 'accepted_quantity', 'rejected_quantity',
    'rejected_returned_quantity', 'accepted_returned_quantity', 'unit_cost_snapshot',
    'accepted_value', 'inspection_result', 'inspection_notes', 'rejection_reason',
])]
class GoodsReceiptLine extends Model
{
    /** @use HasFactory<GoodsReceiptLineFactory> */
    use HasFactory;

    protected $attributes = [
        'accepted_quantity' => 0,
        'rejected_quantity' => 0,
        'rejected_returned_quantity' => 0,
        'accepted_returned_quantity' => 0,
        'accepted_value' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $receipt = GoodsReceipt::query()->find($line->goods_receipt_id);
            $orderLine = PurchaseOrderLine::query()->find($line->purchase_order_line_id);

            if ($receipt === null || $orderLine === null
                || (int) $receipt->company_id !== (int) $line->company_id
                || (int) $orderLine->company_id !== (int) $line->company_id
                || (int) $orderLine->purchase_order_id !== (int) $receipt->purchase_order_id
                || (int) $orderLine->item_id !== (int) $line->item_id
                || (int) $orderLine->unit_of_measure_id !== (int) $line->unit_of_measure_id) {
                throw ValidationException::withMessages([
                    'purchase_order_line_id' => 'The Goods Receipt line must match an item line on its same-company purchase order.',
                ]);
            }

            if (bccomp((string) $line->received_quantity, '0', 4) !== 1) {
                throw ValidationException::withMessages(['received_quantity' => 'Received quantity must be positive.']);
            }

            foreach ([
                'accepted_quantity',
                'rejected_quantity',
                'rejected_returned_quantity',
                'accepted_returned_quantity',
            ] as $quantityField) {
                if (bccomp((string) $line->{$quantityField}, '0', 4) === -1) {
                    throw ValidationException::withMessages([$quantityField => 'Quantities cannot be negative.']);
                }
            }

            if ($line->inspection_result === null) {
                if (bccomp((string) $line->accepted_quantity, '0', 4) !== 0
                    || bccomp((string) $line->rejected_quantity, '0', 4) !== 0) {
                    throw ValidationException::withMessages(['inspection_result' => 'Inspection quantities require an inspection result.']);
                }
            } else {
                $inspectedQuantity = bcadd((string) $line->accepted_quantity, (string) $line->rejected_quantity, 4);
                if (bccomp($inspectedQuantity, (string) $line->received_quantity, 4) !== 0) {
                    throw ValidationException::withMessages(['accepted_quantity' => 'Accepted plus rejected quantity must equal received quantity.']);
                }
                if (bccomp((string) $line->rejected_quantity, '0', 4) === 1 && blank($line->rejection_reason)) {
                    throw ValidationException::withMessages(['rejection_reason' => 'A rejection reason is required for rejected quantity.']);
                }
            }

            if (bccomp((string) $line->rejected_returned_quantity, (string) $line->rejected_quantity, 4) === 1
                || bccomp((string) $line->accepted_returned_quantity, (string) $line->accepted_quantity, 4) === 1) {
                throw ValidationException::withMessages(['returned_quantity' => 'Returned quantities cannot exceed their inspected quantities.']);
            }

            if ($receipt->isEditable()) {
                $line->item_code_snapshot = $orderLine->item_code_snapshot;
                $line->item_name_snapshot = $orderLine->item_name_snapshot;
                $line->uom_snapshot = $orderLine->uom_snapshot;
                $line->unit_cost_snapshot = $orderLine->unit_rate;

                return;
            }

            if ($line->exists) {
                $workflowFields = [
                    'accepted_quantity', 'rejected_quantity', 'rejected_returned_quantity',
                    'accepted_returned_quantity', 'accepted_value', 'inspection_result',
                    'inspection_notes', 'rejection_reason', 'updated_at',
                ];
                if (array_diff(array_keys($line->getDirty()), $workflowFields) !== []) {
                    throw ValidationException::withMessages([
                        'goods_receipt_id' => 'Received Goods Receipt lines are immutable outside inspection and return workflows.',
                    ]);
                }
            }
        });

        static::deleting(function (self $line): void {
            if (! $line->goodsReceipt()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['goods_receipt_id' => 'Only draft Goods Receipt lines may be deleted.']);
            }
        });
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function vendorBillAllocations(): HasMany
    {
        return $this->hasMany(VendorBillReceiptAllocation::class);
    }

    public function availableToInvoice(): string
    {
        $allocated = (string) $this->vendorBillAllocations()
            ->whereHas('vendorBillLine.vendorBill', fn ($query) => $query
                ->where('type', VendorBillType::Invoice->value)
                ->whereNotIn('status', [
                    VendorBillStatus::Draft->value,
                    VendorBillStatus::Rejected->value,
                    VendorBillStatus::Reversed->value,
                ]))
            ->sum('quantity');

        return bcsub(
            bcsub((string) $this->accepted_quantity, (string) $this->accepted_returned_quantity, 4),
            $allocated,
            4,
        );
    }

    public function availableRejectedToReturn(): string
    {
        return bcsub((string) $this->rejected_quantity, (string) $this->rejected_returned_quantity, 4);
    }

    public function availableAcceptedToReturn(): string
    {
        return bcsub((string) $this->accepted_quantity, (string) $this->accepted_returned_quantity, 4);
    }

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'received_quantity' => 'decimal:4',
            'accepted_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'rejected_returned_quantity' => 'decimal:4',
            'accepted_returned_quantity' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
            'accepted_value' => 'decimal:4',
            'inspection_result' => InspectionResult::class,
        ];
    }
}
