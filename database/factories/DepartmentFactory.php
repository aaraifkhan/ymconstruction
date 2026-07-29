<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Administration',
            'Business Development',
            'Finance',
            'Human Resources',
            'Operations',
            'Projects',
            'Procurement',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'company_id' => Company::factory(),
            'parent_department_id' => null,
            'name' => $name,
            'code' => Str::upper(Str::substr(Str::slug($name, ''), 0, 12)),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
