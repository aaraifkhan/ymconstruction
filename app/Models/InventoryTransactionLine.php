<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Database\Factories\InventoryTransactionLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'inventory_transaction_id', 'company_id', 'line_number', 'item_id',
    'unit_of_measure_id', 'goods_receipt_line_id', 'offset_account_id',
    'item_code_snapshot', 'item_name_snapshot', 'uom_snapshot', 'quantity',
    'unit_cost_snapshot', 'line_value', 'notes',
])]
class InventoryTransactionLine extends Model
{
    /** @use HasFactory<InventoryTransactionLineFactory> */
    use HasFactory;

    protected $attributes = [
        'unit_cost_snapshot' => 0,
        'line_value' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $transaction = InventoryTransaction::query()->find($line->inventory_transaction_id);
            $item = Item::query()->whereKey($line->item_id)->where('company_id', $line->company_id)->first();
            $unit = UnitOfMeasure::query()->whereKey($line->unit_of_measure_id)->where('company_id', $line->company_id)->first();

            if ($transaction === null || ! $transaction->isEditable()
                || (int) $transaction->company_id !== (int) $line->company_id
                || $item === null || ! $item->track_inventory
                || $unit === null || (int) $item->unit_of_measure_id !== (int) $unit->getKey()) {
                throw ValidationException::withMessages(['item_id' => 'Inventory lines require an editable same-company transaction and stock-tracked item/UOM.']);
            }

            if (bccomp((string) $line->quantity, '0', 4) !== 1) {
                throw ValidationException::withMessages(['quantity' => 'Inventory transaction quantity must be positive.']);
            }

            if ($transaction->type === InventoryTransactionType::VendorReturn) {
                $receiptLineMatches = GoodsReceiptLine::query()
                    ->whereKey($line->goods_receipt_line_id)
                    ->where('company_id', $line->company_id)
                    ->where('goods_receipt_id', $transaction->goods_receipt_id)
                    ->where('item_id', $line->item_id)
                    ->exists();
                if (! $receiptLineMatches) {
                    throw ValidationException::withMessages(['goods_receipt_line_id' => 'Vendor returns require a matching accepted Goods Receipt line.']);
                }
            } elseif ($line->goods_receipt_line_id !== null) {
                throw ValidationException::withMessages(['goods_receipt_line_id' => 'A Goods Receipt line is only valid for a vendor return.']);
            }

            $requiresOffsetAccount = in_array($transaction->type, [
                InventoryTransactionType::ProjectIssue,
                InventoryTransactionType::ProjectReturn,
                InventoryTransactionType::AdjustmentIncrease,
                InventoryTransactionType::AdjustmentDecrease,
            ], true);
            $account = $line->offset_account_id === null ? null : Account::query()
                ->whereKey($line->offset_account_id)->where('company_id', $line->company_id)->first();

            if ($requiresOffsetAccount && ($account === null || ! $account->is_active || $account->children()->exists())) {
                throw ValidationException::withMessages(['offset_account_id' => 'Select an active posting account from the transaction company.']);
            }

            $line->item_code_snapshot = $item->code;
            $line->item_name_snapshot = $item->name;
            $line->uom_snapshot = $unit->symbol;
        });

        static::deleting(function (self $line): void {
            if (! $line->inventoryTransaction()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['inventory_transaction_id' => 'Posted inventory lines are immutable.']);
            }
        });
    }

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function goodsReceiptLine(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptLine::class);
    }

    public function offsetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'offset_account_id');
    }

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
            'line_value' => 'decimal:4',
        ];
    }
}
