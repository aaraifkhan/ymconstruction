<?php

namespace App\Models;

use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'project_site_id', 'counterparty_site_id', 'project_id', 'item_id',
    'goods_receipt_id', 'inventory_transaction_id', 'customer_invoice_line_id', 'movement_type', 'direction',
    'quantity', 'unit_cost', 'movement_value', 'quantity_after',
    'inventory_value_after', 'average_unit_cost_after', 'actor_id', 'occurred_at',
])]
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $movement): void {
            $sourceCount = collect([
                $movement->goods_receipt_id,
                $movement->inventory_transaction_id,
                $movement->customer_invoice_line_id,
            ])->filter(fn ($id): bool => $id !== null)->count();
            if ($sourceCount !== 1) {
                throw ValidationException::withMessages(['source' => 'Every inventory movement requires exactly one operational source.']);
            }

            $siteMatches = ProjectSite::query()->whereKey($movement->project_site_id)
                ->where('company_id', $movement->company_id)->exists();
            $counterpartyMatches = $movement->counterparty_site_id === null || ProjectSite::query()
                ->whereKey($movement->counterparty_site_id)
                ->where('company_id', $movement->company_id)->exists();
            $projectMatches = $movement->project_id === null || Project::query()
                ->whereKey($movement->project_id)->where('company_id', $movement->company_id)->exists();
            $itemMatches = Item::query()->whereKey($movement->item_id)
                ->where('company_id', $movement->company_id)
                ->where('track_inventory', true)->exists();
            $sourceMatches = match (true) {
                $movement->goods_receipt_id !== null => GoodsReceipt::query()->whereKey($movement->goods_receipt_id)
                    ->where('company_id', $movement->company_id)->exists(),
                $movement->inventory_transaction_id !== null => InventoryTransaction::query()->whereKey($movement->inventory_transaction_id)
                    ->where('company_id', $movement->company_id)->exists(),
                default => CustomerInvoiceLine::query()->whereKey($movement->customer_invoice_line_id)
                    ->where('company_id', $movement->company_id)->exists(),
            };

            if (! $siteMatches || ! $counterpartyMatches || ! $projectMatches || ! $itemMatches || ! $sourceMatches) {
                throw ValidationException::withMessages([
                    'company_id' => 'Inventory movement source, site, Project, and item must belong to one company.',
                ]);
            }

            if (bccomp((string) $movement->quantity, '0', 4) !== 1
                || bccomp((string) $movement->unit_cost, '0', 4) === -1
                || bccomp((string) $movement->movement_value, '0', 4) === -1
                || bccomp((string) $movement->quantity_after, '0', 4) === -1
                || bccomp((string) $movement->inventory_value_after, '0', 4) === -1) {
                throw ValidationException::withMessages(['quantity' => 'Inventory movement quantities and values must remain non-negative.']);
            }
        });

        static::updating(function (): never {
            throw ValidationException::withMessages(['movement' => 'Inventory movements are immutable.']);
        });

        static::deleting(function (): never {
            throw ValidationException::withMessages(['movement' => 'Inventory movements cannot be deleted.']);
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

    public function counterpartySite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'counterparty_site_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function customerInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoiceLine::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'movement_type' => InventoryMovementType::class,
            'direction' => InventoryMovementDirection::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'movement_value' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'inventory_value_after' => 'decimal:4',
            'average_unit_cost_after' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }
}
