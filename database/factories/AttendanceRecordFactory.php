<?php

namespace Database\Factories;

use App\Enums\AttendanceDayStatus;
use App\Enums\AttendanceRecordState;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
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
            'employment_id' => fn (array $attributes) => Employment::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'attendance_date' => fake()->unique()->date(),
            'day_status' => AttendanceDayStatus::MissingPunch,
            'state' => AttendanceRecordState::Draft,
        ];
    }
}
