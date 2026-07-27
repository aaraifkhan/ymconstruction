<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->jobTitle().' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'code' => Str::upper(Str::substr(Str::slug($name, ''), 0, 12)),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
