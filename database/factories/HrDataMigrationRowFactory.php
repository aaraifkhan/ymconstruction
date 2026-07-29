<?php

namespace Database\Factories;

use App\Models\HrDataMigration;
use App\Models\HrDataMigrationRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrDataMigrationRow>
 */
class HrDataMigrationRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $data = ['code' => fake()->unique()->lexify('DEP-???'), 'name' => fake()->words(2, true)];

        return [
            'hr_data_migration_id' => HrDataMigration::factory(),
            'company_id' => fn (array $attributes) => HrDataMigration::findOrFail($attributes['hr_data_migration_id'])->company_id,
            'source_row_number' => fake()->unique()->numberBetween(2, 10000),
            'source_key' => $data['code'],
            'row_checksum' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
            'safe_row_data' => $data,
        ];
    }
}
