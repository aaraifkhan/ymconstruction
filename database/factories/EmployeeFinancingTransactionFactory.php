<?php

namespace Database\Factories;

use App\Enums\EmployeeFinancingTransactionType;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeFinancingTransaction>
 */
class EmployeeFinancingTransactionFactory extends Factory
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
            'type' => EmployeeFinancingTransactionType::Disbursement,
            'effective_date' => now()->toDateString(),
            'principal_amount' => '12000.0000',
            'finance_charge_amount' => '0.0000',
            'total_amount' => '12000.0000',
            'idempotency_key' => fake()->unique()->uuid(),
        ];
    }
}
