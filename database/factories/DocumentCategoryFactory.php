<?php

namespace Database\Factories;

use App\Enums\DocumentClassification;
use App\Models\Company;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentCategory>
 */
class DocumentCategoryFactory extends Factory
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
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->optional()->sentence(),
            'default_classification' => DocumentClassification::Internal,
            'retention_days' => null,
            'requires_expiry' => false,
            'requires_verification' => false,
            'requires_approval' => false,
            'is_active' => true,
        ];
    }

    public function restricted(): static
    {
        return $this->state(fn (): array => [
            'default_classification' => DocumentClassification::Restricted,
        ]);
    }
}
