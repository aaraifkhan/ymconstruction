<?php

namespace Database\Factories;

use App\Models\ProjectBudget;
use App\Models\ProjectBudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectBudgetLine>
 */
class ProjectBudgetLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_budget_id' => ProjectBudget::factory(),
            'company_id' => fn (array $attributes): int => ProjectBudget::query()
                ->findOrFail($attributes['project_budget_id'])
                ->company_id,
            'cost_center_id' => null,
            'item_category_id' => null,
            'cost_code' => fake()->unique()->bothify('COST-####'),
            'description' => fake()->words(4, true),
            'amount' => fake()->randomFloat(4, 1000, 1000000),
            'sort_order' => 0,
        ];
    }
}
