<?php

namespace App\Models;

use Database\Factories\ApMatchingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'quantity_tolerance_percentage', 'rate_tolerance_percentage',
    'tax_tolerance_percentage', 'is_active',
])]
class ApMatchingSetting extends Model
{
    /** @use HasFactory<ApMatchingSettingFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'quantity_tolerance_percentage' => 0,
        'rate_tolerance_percentage' => 0,
        'tax_tolerance_percentage' => 0,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            foreach (['quantity_tolerance_percentage', 'rate_tolerance_percentage', 'tax_tolerance_percentage'] as $field) {
                if (bccomp((string) $setting->{$field}, '0', 4) === -1
                    || bccomp((string) $setting->{$field}, '100', 4) === 1) {
                    throw ValidationException::withMessages([$field => 'Matching tolerance must be between 0 and 100 percent.']);
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('ap_matching_settings')->logOnly([
            'company_id', 'quantity_tolerance_percentage', 'rate_tolerance_percentage',
            'tax_tolerance_percentage', 'is_active',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'quantity_tolerance_percentage' => 'decimal:4',
            'rate_tolerance_percentage' => 'decimal:4',
            'tax_tolerance_percentage' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
