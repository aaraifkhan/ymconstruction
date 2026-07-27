<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Enums\TaxCalculationMethod;
use Database\Factories\PurchaseOrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'purchase_order_id', 'company_id', 'purchase_requisition_line_id', 'line_number',
    'item_id', 'unit_of_measure_id', 'tax_code_id', 'item_code_snapshot',
    'item_name_snapshot', 'uom_snapshot', 'tax_code_snapshot', 'tax_rate_snapshot',
    'tax_calculation_method_snapshot', 'quantity', 'unit_rate', 'line_subtotal',
    'tax_amount', 'line_total', 'received_quantity', 'cancelled_quantity', 'specification',
])]
class PurchaseOrderLine extends Model
{
    /** @use HasFactory<PurchaseOrderLineFactory> */
    use HasFactory;

    protected $attributes = [
        'tax_rate_snapshot' => 0,
        'line_subtotal' => 0,
        'tax_amount' => 0,
        'line_total' => 0,
        'received_quantity' => 0,
        'cancelled_quantity' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $order = PurchaseOrder::query()->find($line->purchase_order_id);
            if ($order === null || (int) $order->company_id !== (int) $line->company_id) {
                throw ValidationException::withMessages(['purchase_order_id' => 'The purchase-order line must belong to the same company.']);
            }

            $receiptFields = ['received_quantity', 'cancelled_quantity', 'updated_at'];
            $isReceiptQuantityUpdate = $line->exists && array_diff(array_keys($line->getDirty()), $receiptFields) === [];
            if (! $order->isEditable() && ! $isReceiptQuantityUpdate) {
                throw ValidationException::withMessages(['purchase_order_id' => 'Submitted purchase-order lines are immutable.']);
            }

            if ($isReceiptQuantityUpdate && ! in_array($order->status, [
                PurchaseOrderStatus::Ordered,
                PurchaseOrderStatus::PartiallyReceived,
                PurchaseOrderStatus::Received,
            ], true)) {
                throw ValidationException::withMessages(['received_quantity' => 'Receipt quantities may only change after the purchase order is issued.']);
            }

            if (bccomp((string) $line->quantity, '0', 4) !== 1 || bccomp((string) $line->unit_rate, '0', 4) === -1) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be positive and unit rate cannot be negative.']);
            }

            $consumedQuantity = bcadd((string) $line->received_quantity, (string) $line->cancelled_quantity, 4);
            if (bccomp((string) $line->received_quantity, '0', 4) === -1
                || bccomp((string) $line->cancelled_quantity, '0', 4) === -1
                || bccomp($consumedQuantity, (string) $line->quantity, 4) === 1) {
                throw ValidationException::withMessages(['received_quantity' => 'Received and cancelled quantities cannot exceed ordered quantity.']);
            }

            $item = Item::query()->whereKey($line->item_id)->where('company_id', $line->company_id)->first();
            $unit = UnitOfMeasure::query()->whereKey($line->unit_of_measure_id)->where('company_id', $line->company_id)->first();
            if ($item === null || $unit === null || (int) $item->unit_of_measure_id !== (int) $unit->getKey()) {
                throw ValidationException::withMessages(['item_id' => 'The item and its unit of measure must belong to the purchase-order company.']);
            }

            if ($line->purchase_requisition_line_id !== null
                && ! PurchaseRequisitionLine::query()->whereKey($line->purchase_requisition_line_id)
                    ->where('company_id', $line->company_id)
                    ->where('purchase_requisition_id', $order->purchase_requisition_id)
                    ->where('item_id', $line->item_id)->exists()) {
                throw ValidationException::withMessages(['purchase_requisition_line_id' => 'The source requisition line must match the purchase order and item.']);
            }

            if ($isReceiptQuantityUpdate) {
                return;
            }

            $taxCode = null;
            if ($line->tax_code_id !== null) {
                $taxCode = TaxCode::query()->whereKey($line->tax_code_id)
                    ->where('company_id', $line->company_id)->first();
                if ($taxCode === null) {
                    throw ValidationException::withMessages(['tax_code_id' => 'The tax code must belong to the purchase-order company.']);
                }
            }

            $line->item_code_snapshot = $item->code;
            $line->item_name_snapshot = $item->name;
            $line->uom_snapshot = $unit->symbol;
            $line->tax_code_snapshot = $taxCode?->code;
            $line->tax_rate_snapshot = $taxCode?->rate ?? '0.0000';
            $line->tax_calculation_method_snapshot = $taxCode?->calculation_method->value;
            $line->line_subtotal = bcmul((string) $line->quantity, (string) $line->unit_rate, 4);

            if ($taxCode?->calculation_method === TaxCalculationMethod::Inclusive) {
                $taxDivisor = bcadd('100.0000', (string) $taxCode->rate, 4);
                $line->tax_amount = bcdiv(
                    bcmul((string) $line->line_subtotal, (string) $taxCode->rate, 4),
                    $taxDivisor,
                    4,
                );
                $line->line_total = $line->line_subtotal;
            } else {
                $line->tax_amount = bcdiv(
                    bcmul((string) $line->line_subtotal, (string) ($taxCode?->rate ?? '0.0000'), 4),
                    '100.0000',
                    4,
                );
                $line->line_total = bcadd((string) $line->line_subtotal, (string) $line->tax_amount, 4);
            }
        });

        static::deleting(function (self $line): void {
            if (! $line->purchaseOrder()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['purchase_order_id' => 'Submitted purchase-order lines are immutable.']);
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requisitionLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionLine::class, 'purchase_requisition_line_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function goodsReceiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function vendorBillLines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class);
    }

    public function availableToReceive(): string
    {
        return bcsub(
            bcsub((string) $this->quantity, (string) $this->received_quantity, 4),
            (string) $this->cancelled_quantity,
            4,
        );
    }

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'unit_rate' => 'decimal:4',
            'tax_rate_snapshot' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'cancelled_quantity' => 'decimal:4',
        ];
    }
}
