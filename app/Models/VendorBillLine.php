<?php

namespace App\Models;

use App\Enums\TaxCalculationMethod;
use Database\Factories\VendorBillLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'vendor_bill_id', 'company_id', 'purchase_order_line_id',
    'original_vendor_bill_line_id', 'item_id', 'unit_of_measure_id', 'tax_code_id',
    'clearing_account_id', 'variance_account_id', 'project_id', 'project_site_id',
    'cost_center_id', 'line_number', 'item_code_snapshot', 'item_name_snapshot',
    'uom_snapshot', 'tax_code_snapshot', 'tax_rate_snapshot',
    'tax_calculation_method_snapshot', 'quantity', 'unit_rate', 'line_subtotal',
    'tax_amount', 'line_total', 'receipt_value', 'price_variance', 'description',
])]
class VendorBillLine extends Model
{
    /** @use HasFactory<VendorBillLineFactory> */
    use HasFactory;

    protected $attributes = [
        'tax_rate_snapshot' => 0,
        'tax_amount' => 0,
        'receipt_value' => 0,
        'price_variance' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $bill = VendorBill::query()->find($line->vendor_bill_id);
            if ($bill === null || (int) $bill->company_id !== (int) $line->company_id) {
                throw ValidationException::withMessages(['vendor_bill_id' => 'Vendor Bill line must belong to the same company.']);
            }
            if (! $bill->isEditable()) {
                $matchingFields = [
                    'clearing_account_id', 'variance_account_id', 'receipt_value',
                    'price_variance', 'updated_at',
                ];
                if (! $line->exists || array_diff(array_keys($line->getDirty()), $matchingFields) !== []) {
                    throw ValidationException::withMessages(['vendor_bill_id' => 'Submitted Vendor Bill lines are immutable.']);
                }

                return;
            }
            if (bccomp((string) $line->quantity, '0', 4) !== 1 || bccomp((string) $line->unit_rate, '0', 4) === -1) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be positive and unit rate cannot be negative.']);
            }

            $orderLine = $line->purchase_order_line_id === null ? null : PurchaseOrderLine::query()
                ->whereKey($line->purchase_order_line_id)
                ->where('company_id', $line->company_id)
                ->where('purchase_order_id', $bill->purchase_order_id)
                ->first();
            if ($line->purchase_order_line_id !== null && $orderLine === null) {
                throw ValidationException::withMessages(['purchase_order_line_id' => 'Purchase Order line must belong to the Vendor Bill order.']);
            }
            if ($orderLine !== null
                && ((int) $orderLine->item_id !== (int) $line->item_id
                    || (int) $orderLine->unit_of_measure_id !== (int) $line->unit_of_measure_id)) {
                throw ValidationException::withMessages(['item_id' => 'Bill item and unit must match the Purchase Order line.']);
            }

            foreach ([
                [Project::class, 'project_id', $line->project_id],
                [ProjectSite::class, 'project_site_id', $line->project_site_id],
                [CostCenter::class, 'cost_center_id', $line->cost_center_id],
                [Account::class, 'clearing_account_id', $line->clearing_account_id],
                [Account::class, 'variance_account_id', $line->variance_account_id],
            ] as [$model, $field, $id]) {
                if ($id === null) {
                    continue;
                }
                if (! $model::query()->whereKey($id)->where('company_id', $line->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => str($field)->headline().' must belong to the Vendor Bill company.']);
                }
            }

            $extendedAmount = bcmul((string) $line->quantity, (string) $line->unit_rate, 4);
            if ($line->tax_calculation_method_snapshot === TaxCalculationMethod::Inclusive->value) {
                $line->line_subtotal = bcsub($extendedAmount, (string) $line->tax_amount, 4);
                $line->line_total = $extendedAmount;
            } else {
                $line->line_subtotal = $extendedAmount;
                $line->line_total = bcadd($extendedAmount, (string) $line->tax_amount, 4);
            }
        });

        static::deleting(function (self $line): void {
            if (! $line->vendorBill()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['vendor_bill_id' => 'Submitted Vendor Bill lines are immutable.']);
            }
        });
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function originalVendorBillLine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_vendor_bill_line_id');
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

    public function clearingAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'clearing_account_id');
    }

    public function varianceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'variance_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VendorBillReceiptAllocation::class);
    }

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'tax_rate_snapshot' => 'decimal:4',
            'quantity' => 'decimal:4',
            'unit_rate' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'receipt_value' => 'decimal:4',
            'price_variance' => 'decimal:4',
        ];
    }
}
