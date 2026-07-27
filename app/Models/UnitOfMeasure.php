<?php

namespace App\Models;

use Database\Factories\UnitOfMeasureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'code', 'name', 'symbol', 'decimal_places', 'is_active'])]
class UnitOfMeasure extends Model
{
    /** @use HasFactory<UnitOfMeasureFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'units_of_measure';

    protected $attributes = [
        'decimal_places' => 4,
        'is_active' => true,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('units_of_measure')
            ->logOnly(['company_id', 'code', 'name', 'symbol', 'decimal_places', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
