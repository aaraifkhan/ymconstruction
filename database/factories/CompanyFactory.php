<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'legal_name' => $name.' (Private) Limited',
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'registration_number' => fake()->optional()->bothify('REG-####-??'),
            'tax_number' => fake()->optional()->numerify('#######-#'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country_code' => 'PK',
            'currency_code' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
