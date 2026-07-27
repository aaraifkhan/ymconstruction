<?php

namespace Database\Factories;

use App\Enums\ProjectBudgetStatus;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectBudget>
 */
class ProjectBudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'company_id' => fn (array $attributes): int => Project::query()
                ->findOrFail($attributes['project_id'])
                ->company_id,
            'version' => 1,
            'status' => ProjectBudgetStatus::Draft,
            'currency_code' => 'PKR',
            'total_amount' => 0,
            'notes' => fake()->optional()->sentence(),
            'prepared_by_id' => User::factory(),
            'approved_by_id' => null,
            'approved_at' => null,
        ];
    }
}
