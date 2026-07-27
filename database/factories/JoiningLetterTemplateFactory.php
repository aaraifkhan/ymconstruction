<?php

namespace Database\Factories;

use App\Actions\JoiningLetters\ProvisionDefaultJoiningLetterTemplateAction;
use App\Models\Company;
use App\Models\JoiningLetterTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JoiningLetterTemplate>
 */
class JoiningLetterTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Joining Letter '.fake()->unique()->numberBetween(1, 99999),
            'code' => 'joining-letter-'.fake()->unique()->numberBetween(1, 99999),
            'subject_template' => 'JOINING LETTER — {{ employee.full_name }}',
            'body_template' => ProvisionDefaultJoiningLetterTemplateAction::bodyTemplate(),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }
}
