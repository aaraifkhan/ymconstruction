<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Models\CustomerInvoiceLine;
use App\Models\GoodsReceipt;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplyInventoryMovementAction
{
    public function handle(
        int $companyId,
        int $siteId,
        int $itemId,
        InventoryMovementDirection $direction,
        string $quantity,
        ?string $unitCost,
        InventoryMovementType $movementType,
        GoodsReceipt|InventoryTransaction|CustomerInvoiceLine $source,
        User $actor,
        ?int $counterpartySiteId = null,
        ?int $projectId = null,
    ): InventoryMovement {
        $balance = InventoryBalance::query()->firstOrCreate([
            'company_id' => $companyId,
            'project_site_id' => $siteId,
            'item_id' => $itemId,
        ]);
        $balance = InventoryBalance::query()->whereKey($balance)->lockForUpdate()->firstOrFail();

        if ($direction === InventoryMovementDirection::Out) {
            if (bccomp((string) $balance->quantity_on_hand, $quantity, 4) === -1) {
                throw ValidationException::withMessages([
                    'quantity' => 'This movement would create negative inventory.',
                ]);
            }

            $unitCost = (string) $balance->average_unit_cost;
            $movementValue = bcmul($quantity, $unitCost, 4);
            $quantityAfter = bcsub((string) $balance->quantity_on_hand, $quantity, 4);
            $valueAfter = bcsub((string) $balance->inventory_value, $movementValue, 4);

            if (bccomp($quantityAfter, '0', 4) === 0) {
                $valueAfter = '0.0000';
            }
        } else {
            if ($unitCost === null || bccomp($unitCost, '0', 4) === -1) {
                throw ValidationException::withMessages(['unit_cost' => 'Inbound inventory requires a non-negative unit cost.']);
            }

            $movementValue = bcmul($quantity, $unitCost, 4);
            $quantityAfter = bcadd((string) $balance->quantity_on_hand, $quantity, 4);
            $valueAfter = bcadd((string) $balance->inventory_value, $movementValue, 4);
        }

        $averageCostAfter = bccomp($quantityAfter, '0', 4) === 0
            ? '0.0000'
            : bcdiv($valueAfter, $quantityAfter, 4);

        $balance->update([
            'quantity_on_hand' => $quantityAfter,
            'inventory_value' => $valueAfter,
            'average_unit_cost' => $averageCostAfter,
        ]);

        return InventoryMovement::query()->create([
            'company_id' => $companyId,
            'project_site_id' => $siteId,
            'counterparty_site_id' => $counterpartySiteId,
            'project_id' => $projectId,
            'item_id' => $itemId,
            'goods_receipt_id' => $source instanceof GoodsReceipt ? $source->getKey() : null,
            'inventory_transaction_id' => $source instanceof InventoryTransaction ? $source->getKey() : null,
            'customer_invoice_line_id' => $source instanceof CustomerInvoiceLine ? $source->getKey() : null,
            'movement_type' => $movementType,
            'direction' => $direction,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'movement_value' => $movementValue,
            'quantity_after' => $quantityAfter,
            'inventory_value_after' => $valueAfter,
            'average_unit_cost_after' => $averageCostAfter,
            'actor_id' => $actor->getKey(),
            'occurred_at' => now(),
        ]);
    }
}
