<?php

namespace Database\Factories;

use App\Enums\JoiningLetterStatus;
use App\Models\Employment;
use App\Models\JoiningLetter;
use App\Models\JoiningLetterTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JoiningLetter>
 */
class JoiningLetterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employment_id' => Employment::factory(),
            'company_id' => fn (array $attributes): int => Employment::query()
                ->findOrFail($attributes['employment_id'])
                ->company_id,
            'joining_letter_template_id' => fn (array $attributes): int => JoiningLetterTemplate::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->getKey(),
            'letter_number' => 'JL-'.fake()->unique()->numerify('#####'),
            'status' => JoiningLetterStatus::Draft,
            'subject' => 'JOINING LETTER',
            'body' => 'Generated joining letter body.',
            'compensation_amount' => '100000.00',
            'currency_code' => 'PKR',
            'letter_date' => today(),
            'employment_effective_date' => today(),
            'created_by_id' => User::factory(),
        ];
    }
}
