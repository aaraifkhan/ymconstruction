<?php

namespace App\Models;

use App\Enums\VoucherType;
use Database\Factories\VoucherSequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'financial_year_id', 'voucher_type', 'prefix', 'next_number', 'padding', 'is_active'])]
class VoucherSequence extends Model
{
    /** @use HasFactory<VoucherSequenceFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $sequence): void {
            if ((int) $sequence->financialYear?->company_id !== (int) $sequence->company_id) {
                throw ValidationException::withMessages(['financial_year_id' => 'Voucher sequence and financial year must belong to the same company.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    protected function casts(): array
    {
        return ['voucher_type' => VoucherType::class, 'is_active' => 'boolean'];
    }
}
