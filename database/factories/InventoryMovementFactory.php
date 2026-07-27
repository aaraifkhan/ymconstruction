<?php

namespace Database\Factories;

use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_transaction_id' => InventoryTransaction::factory(),
            'goods_receipt_id' => null,
            'company_id' => fn (array $attributes): int => InventoryTransaction::query()
                ->findOrFail($attributes['inventory_transaction_id'])->company_id,
            'project_site_id' => fn (array $attributes): int => InventoryTransaction::query()
                ->findOrFail($attributes['inventory_transaction_id'])->destination_site_id,
            'counterparty_site_id' => null,
            'project_id' => null,
            'item_id' => fn (array $attributes): int => Item::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'movement_type' => InventoryMovementType::AdjustmentIncrease,
            'direction' => InventoryMovementDirection::In,
            'quantity' => 1,
            'unit_cost' => 100,
            'movement_value' => 100,
            'quantity_after' => 1,
            'inventory_value_after' => 100,
            'average_unit_cost_after' => 100,
            'actor_id' => User::factory(),
            'occurred_at' => now(),
        ];
    }
}
