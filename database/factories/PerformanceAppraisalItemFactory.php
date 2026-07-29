<?php

namespace Database\Factories;

use App\Models\PerformanceAppraisal;
use App\Models\PerformanceAppraisalItem;
use App\Models\PerformanceKpi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceAppraisalItem>
 */
class PerformanceAppraisalItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'performance_appraisal_id' => PerformanceAppraisal::factory(),
            'company_id' => fn (array $attributes) => PerformanceAppraisal::query()
                ->findOrFail($attributes['performance_appraisal_id'])->company_id,
            'performance_kpi_id' => fn (array $attributes) => PerformanceKpi::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'goal' => fake()->sentence(),
            'weight' => 100,
        ];
    }
}
