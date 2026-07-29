<?php

namespace Database\Factories;

use App\Models\AttendanceImportBatch;
use App\Models\AttendanceImportRowError;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceImportRowError>
 */
class AttendanceImportRowErrorFactory extends Factory
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
            'attendance_import_batch_id' => fn (array $attributes) => AttendanceImportBatch::factory()
                ->state(['company_id' => $attributes['company_id']]),
            'row_number' => 2,
            'error_code' => 'validation_failed',
            'message' => fake()->sentence(),
        ];
    }
}
