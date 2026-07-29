<?php

namespace Database\Factories;

use App\Enums\EmploymentMovementStatus;
use App\Enums\EmploymentMovementType;
use App\Models\Department;
use App\Models\Employment;
use App\Models\EmploymentMovementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentMovementRequest>
 */
class EmploymentMovementRequestFactory extends Factory
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
            'reference_number' => 'MOV-'.fake()->unique()->numerify('######'),
            'type' => EmploymentMovementType::Transfer,
            'status' => EmploymentMovementStatus::Draft,
            'effective_on' => '2026-08-01',
            'target_department_id' => fn (array $attributes) => Department::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'reason' => fake()->sentence(),
        ];
    }
}
