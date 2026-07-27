<?php

namespace App\Models;

use Database\Factories\InventoryBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'project_site_id', 'item_id', 'quantity_on_hand',
    'inventory_value', 'average_unit_cost',
])]
class InventoryBalance extends Model
{
    /** @use HasFactory<InventoryBalanceFactory> */
    use HasFactory;

    protected $attributes = [
        'quantity_on_hand' => 0,
        'inventory_value' => 0,
        'average_unit_cost' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $balance): void {
            $siteMatches = ProjectSite::query()->whereKey($balance->project_site_id)
                ->where('company_id', $balance->company_id)->exists();
            $item = Item::query()->whereKey($balance->item_id)
                ->where('company_id', $balance->company_id)->first();

            if (! $siteMatches || $item === null || ! $item->track_inventory) {
                throw ValidationException::withMessages([
                    'item_id' => 'Inventory balances require a stock-tracked item and site from the same company.',
                ]);
            }

            if (bccomp((string) $balance->quantity_on_hand, '0', 4) === -1
                || bccomp((string) $balance->inventory_value, '0', 4) === -1
                || bccomp((string) $balance->average_unit_cost, '0', 4) === -1) {
                throw ValidationException::withMessages(['quantity_on_hand' => 'Inventory balances cannot be negative.']);
            }
        });

        static::deleting(function (self $balance): void {
            if (bccomp((string) $balance->quantity_on_hand, '0', 4) !== 0
                || InventoryMovement::query()
                    ->where('company_id', $balance->company_id)
                    ->where('project_site_id', $balance->project_site_id)
                    ->where('item_id', $balance->item_id)
                    ->exists()) {
                throw ValidationException::withMessages(['quantity_on_hand' => 'Inventory history and non-zero balances cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'inventory_value' => 'decimal:4',
            'average_unit_cost' => 'decimal:4',
        ];
    }
}
