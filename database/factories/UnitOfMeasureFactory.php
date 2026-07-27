<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitOfMeasure>
 */
class UnitOfMeasureFactory extends Factory
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
            'code' => fake()->unique()->bothify('UOM-###'),
            'name' => fake()->unique()->word(),
            'symbol' => fake()->lexify('???'),
            'decimal_places' => 4,
            'is_active' => true,
        ];
    }
}
