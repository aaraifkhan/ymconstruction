<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PerformanceKpi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceKpi>
 */
class PerformanceKpiFactory extends Factory
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
            'code' => 'KPI-'.fake()->unique()->numerify('####'),
            'name' => fake()->sentence(3),
            'measurement_unit' => 'Configured unit',
            'is_active' => true,
        ];
    }
}
