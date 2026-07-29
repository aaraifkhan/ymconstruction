<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollCalculationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollCalculationRule>
 */
class PayrollCalculationRuleFactory extends Factory
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
            'name' => 'Standard '.fake()->unique()->word(),
            'effective_from' => '2026-01-01',
            'requires_finalized_attendance' => true,
            'prorate_allowances' => false,
            'absence_day_factor' => 1,
            'unpaid_leave_day_factor' => 1,
            'half_day_factor' => 0.5,
            'deduct_late_minutes' => true,
            'standard_day_minutes' => 480,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company->getKey()]);
    }
}
