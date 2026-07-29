<?php

namespace Database\Factories;

use App\Enums\AppraisalCycleStatus;
use App\Models\AppraisalCycle;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppraisalCycle>
 */
class AppraisalCycleFactory extends Factory
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
            'name' => 'Cycle '.fake()->unique()->numerify('####'),
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'score_min' => 1,
            'score_max' => 5,
            'status' => AppraisalCycleStatus::Draft,
        ];
    }
}
