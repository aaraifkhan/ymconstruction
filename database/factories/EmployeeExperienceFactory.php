<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeExperience>
 */
class EmployeeExperienceFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-12 years', '-2 years');

        return [
            'employee_id' => Employee::factory(),
            'company_name' => fake()->company(),
            'designation' => fake()->jobTitle(),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, '-1 year'),
            'duration_text' => null,
            'reason_for_leaving' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
