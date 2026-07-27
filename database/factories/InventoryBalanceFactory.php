<?php

namespace Database\Factories;

use App\Models\InventoryBalance;
use App\Models\Item;
use App\Models\ProjectSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_site_id' => ProjectSite::factory(),
            'company_id' => fn (array $attributes): int => ProjectSite::query()
                ->findOrFail($attributes['project_site_id'])->company_id,
            'item_id' => fn (array $attributes): int => Item::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'quantity_on_hand' => 0,
            'inventory_value' => 0,
            'average_unit_cost' => 0,
        ];
    }
}
