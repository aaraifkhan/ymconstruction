<?php

namespace Database\Factories;

use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceDeviceTransport;
use App\Models\AttendanceDevice;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceDevice>
 */
class AttendanceDeviceFactory extends Factory
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
            'code' => fake()->unique()->bothify('DEV-###'),
            'name' => fake()->words(2, true),
            'device_identifier' => fake()->unique()->uuid(),
            'timezone' => 'Asia/Karachi',
            'transport' => AttendanceDeviceTransport::Unknown,
            'health_status' => AttendanceDeviceHealthStatus::Unknown,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
