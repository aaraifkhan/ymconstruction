<?php

namespace Database\Factories;

use App\Enums\HrDataMigrationStatus;
use App\Enums\HrDataMigrationType;
use App\Models\Company;
use App\Models\HrDataMigration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HrDataMigration>
 */
class HrDataMigrationFactory extends Factory
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
            'type' => HrDataMigrationType::Departments,
            'idempotency_key' => Str::uuid(),
            'source_filename' => 'departments.csv',
            'source_checksum' => hash('sha256', fake()->uuid()),
            'status' => HrDataMigrationStatus::Draft,
            'prepared_by_id' => User::factory(),
        ];
    }
}
