<?php

namespace Database\Factories;

use App\Enums\EmploymentAccessReviewStatus;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\EmploymentSeparationType;
use App\Models\Employment;
use App\Models\EmploymentSeparation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentSeparation>
 */
class EmploymentSeparationFactory extends Factory
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
            'reference_number' => 'SEP-'.fake()->unique()->numerify('######'),
            'type' => EmploymentSeparationType::Resignation,
            'status' => EmploymentSeparationStatus::Draft,
            'request_date' => '2026-07-01',
            'proposed_last_working_date' => '2026-07-31',
            'reason' => fake()->sentence(),
            'access_review_status' => EmploymentAccessReviewStatus::Pending,
        ];
    }
}
