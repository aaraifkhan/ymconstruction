<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransactionLine>
 */
class InventoryTransactionLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_transaction_id' => InventoryTransaction::factory(),
            'company_id' => fn (array $attributes): int => InventoryTransaction::query()
                ->findOrFail($attributes['inventory_transaction_id'])->company_id,
            'line_number' => 1,
            'item_id' => fn (array $attributes): int => Item::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'unit_of_measure_id' => fn (array $attributes): int => Item::query()
                ->findOrFail($attributes['item_id'])->unit_of_measure_id,
            'goods_receipt_line_id' => null,
            'offset_account_id' => fn (array $attributes): ?int => Account::query()
                ->where('company_id', $attributes['company_id'])
                ->where('is_active', true)
                ->where('allows_manual_posting', true)
                ->whereDoesntHave('children')
                ->value('id'),
            'item_code_snapshot' => 'pending',
            'item_name_snapshot' => 'pending',
            'uom_snapshot' => 'pending',
            'quantity' => 1,
            'unit_cost_snapshot' => 100,
            'line_value' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
