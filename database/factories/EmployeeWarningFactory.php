<?php

namespace Database\Factories;

use App\Enums\EmployeeWarningStatus;
use App\Models\EmployeeWarning;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeWarning>
 */
class EmployeeWarningFactory extends Factory
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
            'reference_number' => 'WRN-'.fake()->unique()->numerify('######'),
            'level' => 'Configured level',
            'incident_date' => '2026-07-01',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => EmployeeWarningStatus::Draft,
        ];
    }
}
