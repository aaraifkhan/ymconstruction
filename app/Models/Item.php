<?php

namespace App\Models;

use App\Enums\ItemType;
use Database\Factories\ItemFactory;
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
    'item_category_id',
    'unit_of_measure_id',
    'default_tax_code_id',
    'code',
    'name',
    'type',
    'description',
    'track_inventory',
    'is_active',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'type' => ItemType::Material->value,
        'track_inventory' => true,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Item $item): void {
            $relatedModels = [
                'item_category_id' => [ItemCategory::class, $item->item_category_id],
                'unit_of_measure_id' => [UnitOfMeasure::class, $item->unit_of_measure_id],
                'default_tax_code_id' => [TaxCode::class, $item->default_tax_code_id],
            ];

            foreach ($relatedModels as $field => [$model, $relatedId]) {
                if ($relatedId !== null && ! $model::query()->whereKey($relatedId)->where('company_id', $item->company_id)->exists()) {
                    throw ValidationException::withMessages([
                        $field => 'The selected record must belong to the same company.',
                    ]);
                }
            }

            if ($item->type === ItemType::Service && $item->track_inventory) {
                throw ValidationException::withMessages([
                    'track_inventory' => 'Services cannot track inventory quantities.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function defaultTaxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class, 'default_tax_code_id');
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('items')
            ->logOnly([
                'company_id',
                'item_category_id',
                'unit_of_measure_id',
                'default_tax_code_id',
                'code',
                'name',
                'type',
                'description',
                'track_inventory',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
