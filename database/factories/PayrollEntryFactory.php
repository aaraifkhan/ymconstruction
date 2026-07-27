<?php

namespace Database\Factories;

use App\Enums\EmploymentCategory;
use App\Models\Employment;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollEntry>
 */
class PayrollEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'company_id' => fn (array $attributes): int => PayrollRun::query()->findOrFail($attributes['payroll_run_id'])->company_id,
            'employment_id' => fn (array $attributes): int => Employment::factory()->create(['company_id' => $attributes['company_id']])->getKey(),
            'employee_name' => fake()->name(),
            'employee_code' => 'EMP-'.fake()->unique()->numerify('#####'),
            'designation' => 'Staff',
            'employment_category' => EmploymentCategory::AdministrativeStaff->value,
            'period_days' => 30,
            'payable_days' => 30,
            'basic_salary' => 100000,
            'payable_basic' => 100000,
            'house_travel_allowance' => 15000,
            'food_allowance' => 10000,
            'other_allowance' => 0,
            'gross_salary' => 125000,
            'absence_deduction' => 0,
            'loan_advance_deduction' => 0,
            'other_deduction' => 0,
            'net_salary' => 125000,
            'bank_amount' => 125000,
            'cash_amount' => 0,
            'currency_code' => 'PKR',
        ];
    }
}
