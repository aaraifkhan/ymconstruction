<?php

namespace Database\Factories;

use App\Enums\EmployeeClearanceStatus;
use App\Models\Company;
use App\Models\EmployeeClearance;
use App\Models\Employment;
use App\Models\EmploymentSeparation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeClearance>
 */
class EmployeeClearanceFactory extends Factory
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
            'employment_id' => fn (array $attributes) => Employment::factory()->forCompany(
                Company::query()->findOrFail($attributes['company_id']),
            ),
            'employment_separation_id' => fn (array $attributes) => EmploymentSeparation::factory()->create([
                'company_id' => $attributes['company_id'],
                'employment_id' => $attributes['employment_id'],
            ]),
            'reference_number' => fake()->unique()->bothify('CLR-######'),
            'status' => EmployeeClearanceStatus::Draft,
            'prepared_by_id' => User::factory(),
        ];
    }
}
