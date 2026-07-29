<?php

namespace Database\Factories;

use App\Enums\PerformanceAppraisalStatus;
use App\Models\AppraisalCycle;
use App\Models\Employment;
use App\Models\PerformanceAppraisal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceAppraisal>
 */
class PerformanceAppraisalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employment_id' => Employment::factory(),
            'company_id' => fn (array $attributes) => Employment::query()
                ->findOrFail($attributes['employment_id'])->company_id,
            'appraisal_cycle_id' => fn (array $attributes) => AppraisalCycle::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'status' => PerformanceAppraisalStatus::Draft,
        ];
    }
}
