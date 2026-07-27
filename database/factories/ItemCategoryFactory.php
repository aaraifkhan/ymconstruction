<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemCategory>
 */
class ItemCategoryFactory extends Factory
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
            'code' => fake()->unique()->bothify('CAT-####'),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
