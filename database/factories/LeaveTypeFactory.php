<?php

namespace Database\Factories;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveUnit;
use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
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
            'code' => fake()->unique()->bothify('LV-###'),
            'name' => fake()->unique()->words(2, true),
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => false,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
