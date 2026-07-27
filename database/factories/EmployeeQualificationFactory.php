<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeQualification>
 */
class EmployeeQualificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'qualification' => fake()->randomElement(['Matric', 'Intermediate', 'Bachelor', 'Master']),
            'institution' => fake()->company(),
            'field_of_study' => fake()->randomElement(['Business', 'Engineering', 'Finance', 'Management']),
            'completion_year' => fake()->numberBetween(1990, (int) now()->format('Y')),
            'grade' => fake()->optional()->randomElement(['A', 'B', 'First Division', 'Second Division']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
