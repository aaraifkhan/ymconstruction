<?php

namespace Database\Factories;

use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'document_category_id' => fn (array $attributes): int => DocumentCategory::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->getKey(),
            'documentable_type' => Company::class,
            'documentable_id' => fn (array $attributes): int => $attributes['company_id'],
            'title' => fake()->sentence(4),
            'reference_number' => fake()->optional()->bothify('DOC-####-??'),
            'classification' => DocumentClassification::Internal,
            'status' => DocumentStatus::Draft,
            'issue_date' => fake()->optional()->date(),
            'expiry_date' => null,
            'description' => fake()->optional()->paragraph(),
            'metadata' => null,
        ];
    }

    public function confidential(): static
    {
        return $this->state(fn (): array => [
            'classification' => DocumentClassification::Confidential,
        ]);
    }
}
