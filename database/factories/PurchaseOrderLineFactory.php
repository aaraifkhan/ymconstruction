<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderLine>
 */
class PurchaseOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'company_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->company_id,
            'purchase_requisition_line_id' => null,
            'line_number' => 1,
            'item_id' => fn (array $attributes): int => Item::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'unit_of_measure_id' => fn (array $attributes): int => Item::query()
                ->findOrFail($attributes['item_id'])->unit_of_measure_id,
            'tax_code_id' => null,
            'item_code_snapshot' => 'pending',
            'item_name_snapshot' => 'pending',
            'uom_snapshot' => 'pending',
            'tax_code_snapshot' => null,
            'tax_rate_snapshot' => 0,
            'tax_calculation_method_snapshot' => null,
            'quantity' => fake()->randomFloat(4, 1, 100),
            'unit_rate' => fake()->randomFloat(4, 1, 10000),
            'line_subtotal' => 0,
            'tax_amount' => 0,
            'line_total' => 0,
            'received_quantity' => 0,
            'cancelled_quantity' => 0,
            'specification' => fake()->optional()->sentence(),
        ];
    }
}
