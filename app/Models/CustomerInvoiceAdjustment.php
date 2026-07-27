<?php

namespace App\Models;

use App\Enums\CustomerInvoiceAdjustmentType;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\TaxCodeType;
use Database\Factories\CustomerInvoiceAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['customer_invoice_id', 'company_id', 'tax_code_id', 'type', 'description', 'amount'])]
class CustomerInvoiceAdjustment extends Model
{
    /** @use HasFactory<CustomerInvoiceAdjustmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $adjustment): void {
            $invoice = CustomerInvoice::query()->find($adjustment->customer_invoice_id);
            if ($invoice === null || (int) $invoice->company_id !== (int) $adjustment->company_id
                || ! $invoice->isEditable() || $invoice->category !== CustomerInvoiceCategory::RunningBill) {
                throw ValidationException::withMessages(['customer_invoice_id' => 'Adjustments require an editable same-company Running Bill.']);
            }
            if (bccomp((string) $adjustment->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Adjustment amount must be positive.']);
            }
            if ($adjustment->type === CustomerInvoiceAdjustmentType::WithholdingTax) {
                $tax = TaxCode::query()->whereKey($adjustment->tax_code_id)
                    ->where('company_id', $adjustment->company_id)->where('type', TaxCodeType::WithholdingTax)->first();
                if ($tax === null) {
                    throw ValidationException::withMessages(['tax_code_id' => 'WHT adjustment requires a same-company Withholding Tax code.']);
                }
            } elseif ($adjustment->tax_code_id !== null) {
                throw ValidationException::withMessages(['tax_code_id' => 'Only WHT adjustments use a Tax Code.']);
            }
        });
    }

    public function customerInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    protected function casts(): array
    {
        return ['type' => CustomerInvoiceAdjustmentType::class, 'amount' => 'decimal:4'];
    }
}
