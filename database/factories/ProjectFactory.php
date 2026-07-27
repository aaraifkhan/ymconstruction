<?php

namespace Database\Factories;

use App\Enums\PartyRole;
use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\Party;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'client_party_id' => fn (array $attributes): int => Party::factory()
                ->create([
                    'company_id' => $attributes['company_id'],
                    'roles' => [PartyRole::Customer->value],
                ])
                ->getKey(),
            'consultant_party_id' => null,
            'code' => fake()->unique()->bothify('PRJ-#####'),
            'name' => fake()->words(4, true),
            'location' => fake()->address(),
            'planned_start_date' => today(),
            'planned_completion_date' => today()->addYear(),
            'actual_start_date' => null,
            'actual_completion_date' => null,
            'contract_value' => fake()->randomFloat(4, 100000, 10000000),
            'retention_terms' => null,
            'mobilization_terms' => null,
            'currency_code' => 'PKR',
            'status' => ProjectStatus::Planned,
        ];
    }
}
