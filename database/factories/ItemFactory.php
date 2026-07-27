<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Company;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'item_category_id' => fn (array $attributes): int => ItemCategory::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->getKey(),
            'unit_of_measure_id' => fn (array $attributes): int => UnitOfMeasure::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->getKey(),
            'default_tax_code_id' => null,
            'code' => fake()->unique()->bothify('ITEM-#####'),
            'name' => fake()->words(3, true),
            'type' => ItemType::Material,
            'description' => fake()->optional()->sentence(),
            'track_inventory' => true,
            'is_active' => true,
        ];
    }
}
