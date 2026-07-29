<?php

namespace Database\Factories;

use App\Enums\AttendanceCorrectionStatus;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
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
            'attendance_record_id' => fn (array $attributes) => AttendanceRecord::factory()
                ->state(['company_id' => $attributes['company_id']]),
            'status' => AttendanceCorrectionStatus::Pending,
            'before_snapshot' => ['day_status' => 'missing_punch'],
            'proposed_snapshot' => ['day_status' => 'present'],
            'reason' => fake()->sentence(),
            'requested_by_id' => User::factory(),
        ];
    }
}
