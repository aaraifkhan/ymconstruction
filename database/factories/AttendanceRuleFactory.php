<?php

namespace Database\Factories;

use App\Enums\MissingPunchTreatment;
use App\Models\AttendanceRule;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRule>
 */
class AttendanceRuleFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'effective_from' => now()->startOfYear(),
            'grace_minutes' => 10,
            'late_rounding_minutes' => 5,
            'half_day_after_minutes' => 120,
            'absence_after_minutes' => 240,
            'minimum_overtime_minutes' => 30,
            'missing_punch_treatment' => MissingPunchTreatment::Flag,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
