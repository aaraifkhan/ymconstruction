<?php

namespace Database\Factories;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceDevice;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceRawEvent;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRawEvent>
 */
class AttendanceRawEventFactory extends Factory
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
            'attendance_device_id' => fn (array $attributes) => AttendanceDevice::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'attendance_import_batch_id' => fn (array $attributes) => AttendanceImportBatch::factory()
                ->state(['company_id' => $attributes['company_id']]),
            'external_user_id' => fake()->numerify('####'),
            'original_punched_at_local' => '2026-07-28 09:00:00',
            'timezone' => 'Asia/Karachi',
            'punched_at_utc' => '2026-07-28 04:00:00',
            'direction' => AttendancePunchDirection::In,
            'source_event_id' => fake()->unique()->uuid(),
            'event_fingerprint' => hash('sha256', fake()->unique()->uuid()),
            'processing_status' => AttendanceRawEventStatus::Pending,
            'received_at' => now(),
        ];
    }
}
