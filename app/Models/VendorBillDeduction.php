<?php

namespace App\Models;

use App\Enums\TaxCodeType;
use App\Enums\VendorBillDeductionType;
use Database\Factories\VendorBillDeductionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'vendor_bill_id', 'company_id', 'tax_code_id', 'account_id', 'type',
    'description', 'rate_snapshot', 'amount',
])]
class VendorBillDeduction extends Model
{
    /** @use HasFactory<VendorBillDeductionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $deduction): void {
            $bill = VendorBill::query()->find($deduction->vendor_bill_id);
            if ($bill === null || (int) $bill->company_id !== (int) $deduction->company_id || ! $bill->isEditable()) {
                throw ValidationException::withMessages(['vendor_bill_id' => 'Deduction must belong to an editable same-company Vendor Bill.']);
            }
            if (bccomp((string) $deduction->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Deduction amount must be positive.']);
            }
            if ($deduction->account_id !== null
                && ! Account::query()->whereKey($deduction->account_id)->where('company_id', $deduction->company_id)->exists()) {
                throw ValidationException::withMessages(['account_id' => 'Deduction account must belong to the Vendor Bill company.']);
            }
            if ($deduction->type === VendorBillDeductionType::Other && $deduction->account_id === null) {
                throw ValidationException::withMessages(['account_id' => 'Other deductions require an explicit same-company account.']);
            }
            if ($deduction->type === VendorBillDeductionType::WithholdingTax) {
                $taxCode = TaxCode::query()
                    ->whereKey($deduction->tax_code_id)
                    ->where('company_id', $deduction->company_id)
                    ->where('type', TaxCodeType::WithholdingTax)
                    ->activeOn($bill->invoice_date->toDateString())
                    ->first();
                if ($taxCode === null) {
                    throw ValidationException::withMessages(['tax_code_id' => 'WHT deduction requires an effective active same-company WHT code.']);
                }
                $deduction->rate_snapshot = $taxCode->rate;
            }
        });

        static::deleting(function (self $deduction): void {
            if (! $deduction->vendorBill()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['vendor_bill_id' => 'Submitted Vendor Bill deductions are immutable.']);
            }
        });
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected function casts(): array
    {
        return [
            'type' => VendorBillDeductionType::class,
            'rate_snapshot' => 'decimal:4',
            'amount' => 'decimal:4',
        ];
    }
}
