<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'father_or_husband_name' => fake()->name(),
            'cnic' => fake()->unique()->numerify('#####-#######-#'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'nationality' => 'Pakistani',
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'address' => fake()->address(),
            'mobile' => fake()->numerify('03#########'),
            'alternate_contact' => fake()->optional()->numerify('03#########'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
