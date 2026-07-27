<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCenter>
 */
class CostCenterFactory extends Factory
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
            'code' => 'CC-'.fake()->unique()->numerify('####'),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
