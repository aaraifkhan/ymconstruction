<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $path = 'documents/factory/'.Str::uuid().'.pdf';

        return [
            'document_id' => Document::factory(),
            'version' => 1,
            'disk' => 'local',
            'path' => $path,
            'original_file_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => fake()->numberBetween(100, 100000),
            'checksum' => hash('sha256', $path),
            'uploaded_by_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
