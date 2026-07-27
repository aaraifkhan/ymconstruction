<?php

namespace App\Models;

use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use Database\Factories\TaxCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'code',
    'name',
    'type',
    'rate',
    'calculation_method',
    'effective_from',
    'effective_to',
    'is_recoverable',
    'is_active',
    'notes',
])]
class TaxCode extends Model
{
    /** @use HasFactory<TaxCodeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'calculation_method' => TaxCalculationMethod::Exclusive->value,
        'is_recoverable' => false,
        'is_active' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (TaxCode $taxCode): void {
            if ($taxCode->effective_to !== null && $taxCode->effective_to->lt($taxCode->effective_from)) {
                throw ValidationException::withMessages([
                    'effective_to' => 'The effective end date must be on or after the start date.',
                ]);
            }

            $overlapExists = static::query()
                ->where('company_id', $taxCode->company_id)
                ->where('code', $taxCode->code)
                ->whereKeyNot($taxCode)
                ->whereDate('effective_from', '<=', $taxCode->effective_to ?? '9999-12-31')
                ->where(function (Builder $query) use ($taxCode): void {
                    $query
                        ->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $taxCode->effective_from);
                })
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'effective_from' => 'Effective dates cannot overlap another version of this tax code.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function defaultForItems(): HasMany
    {
        return $this->hasMany(Item::class, 'default_tax_code_id');
    }

    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('effective_to')
                ->orWhereDate('effective_to', '>=', $date));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tax_codes')
            ->logOnly([
                'company_id',
                'code',
                'name',
                'type',
                'rate',
                'calculation_method',
                'effective_from',
                'effective_to',
                'is_recoverable',
                'is_active',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => TaxCodeType::class,
            'rate' => 'decimal:4',
            'calculation_method' => TaxCalculationMethod::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_recoverable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
