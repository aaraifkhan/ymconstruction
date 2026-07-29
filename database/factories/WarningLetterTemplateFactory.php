<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WarningLetterTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarningLetterTemplate>
 */
class WarningLetterTemplateFactory extends Factory
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
            'code' => 'WARN-'.fake()->unique()->numerify('####'),
            'name' => fake()->sentence(3),
            'level' => 'Configured level',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'requires_response' => true,
            'is_active' => true,
        ];
    }
}
