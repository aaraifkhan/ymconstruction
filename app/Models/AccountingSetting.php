<?php

namespace App\Models;

use App\Enums\AccountingProfile;
use App\Enums\InventoryValuationMethod;
use Database\Factories\AccountingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'profile', 'base_currency_code', 'timezone', 'fiscal_year_start_month', 'fiscal_year_start_day', 'monetary_precision', 'display_precision', 'inventory_valuation_method', 'allow_negative_inventory'])]
class AccountingSetting extends Model
{
    /** @use HasFactory<AccountingSettingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            if ($setting->fiscal_year_start_month < 1 || $setting->fiscal_year_start_month > 12 || $setting->fiscal_year_start_day !== 1) {
                throw ValidationException::withMessages(['fiscal_year_start_month' => 'Fiscal year must start on the first day of a valid month.']);
            }
            if ($setting->display_precision > $setting->monetary_precision) {
                throw ValidationException::withMessages(['display_precision' => 'Display precision cannot exceed monetary precision.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'profile' => AccountingProfile::class,
            'inventory_valuation_method' => InventoryValuationMethod::class,
            'allow_negative_inventory' => 'boolean',
        ];
    }
}
