<?php

namespace Database\Factories;

use App\Enums\ProjectSiteType;
use App\Models\Project;
use App\Models\ProjectSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectSite>
 */
class ProjectSiteFactory extends Factory
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
            'cost_center_id' => null,
            'code' => fake()->unique()->bothify('SITE-####'),
            'name' => fake()->words(3, true),
            'type' => ProjectSiteType::Site,
            'location' => fake()->address(),
            'is_active' => true,
        ];
    }
}
