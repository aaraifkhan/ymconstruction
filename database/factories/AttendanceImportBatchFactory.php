<?php

namespace Database\Factories;

use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use App\Models\AttendanceImportBatch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceImportBatch>
 */
class AttendanceImportBatchFactory extends Factory
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
            'source' => AttendanceImportSource::Manual,
            'status' => AttendanceImportBatchStatus::Pending,
            'batch_checksum' => hash('sha256', fake()->unique()->uuid()),
        ];
    }
}
