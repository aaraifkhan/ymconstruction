<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkLocation>
 */
class WorkLocationFactory extends Factory
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
            'code' => fake()->unique()->bothify('LOC-###'),
            'name' => fake()->unique()->company().' Office',
            'address' => fake()->optional()->address(),
            'is_active' => true,
        ];
    }
}
