<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeEmergencyContact>
 */
class EmployeeEmergencyContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Parent', 'Spouse', 'Sibling', 'Friend']),
            'mobile' => fake()->numerify('03#########'),
            'address' => fake()->optional()->address(),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => ['is_primary' => true]);
    }
}
