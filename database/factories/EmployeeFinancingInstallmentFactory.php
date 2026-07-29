<?php

namespace Database\Factories;

use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeFinancingInstallment>
 */
class EmployeeFinancingInstallmentFactory extends Factory
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
            'employee_financing_id' => fn (array $attributes) => EmployeeFinancing::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'schedule_version' => 1,
            'installment_number' => 1,
            'due_date' => now()->addMonth()->startOfMonth(),
            'principal_due' => '4000.0000',
            'finance_charge_due' => '0.0000',
            'total_due' => '4000.0000',
            'status' => EmployeeFinancingInstallmentStatus::Pending,
        ];
    }
}
