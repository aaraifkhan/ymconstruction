<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequisitionLine>
 */
class PurchaseRequisitionLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_requisition_id' => PurchaseRequisition::factory(),
            'company_id' => fn (array $attributes): int => PurchaseRequisition::query()
                ->findOrFail($attributes['purchase_requisition_id'])->company_id,
            'line_number' => 1,
            'item_id' => fn (array $attributes): int => Item::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'unit_of_measure_id' => fn (array $attributes): int => Item::query()
                ->findOrFail($attributes['item_id'])->unit_of_measure_id,
            'project_budget_line_id' => null,
            'item_code_snapshot' => 'pending',
            'item_name_snapshot' => 'pending',
            'uom_snapshot' => 'pending',
            'quantity' => fake()->randomFloat(4, 1, 100),
            'estimated_rate' => fake()->randomFloat(4, 1, 10000),
            'estimated_amount' => 0,
            'ordered_quantity' => 0,
            'specification' => fake()->optional()->sentence(),
        ];
    }
}
