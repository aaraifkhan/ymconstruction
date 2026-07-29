<?php

namespace Database\Factories;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceUserMapping;
use App\Models\Company;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceDeviceUserMapping>
 */
class AttendanceDeviceUserMappingFactory extends Factory
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
            'employment_id' => fn (array $attributes) => Employment::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'external_user_id' => fake()->unique()->numerify('####'),
            'effective_from' => now()->startOfYear(),
        ];
    }
}
