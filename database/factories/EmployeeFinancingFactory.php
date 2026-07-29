<?php

namespace Database\Factories;

use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeFinancing>
 */
class EmployeeFinancingFactory extends Factory
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
            'type' => EmployeeFinancingType::Advance,
            'status' => EmployeeFinancingStatus::Draft,
            'request_date' => now()->toDateString(),
            'purpose' => fake()->sentence(),
            'principal_amount' => '12000.0000',
            'finance_charge' => '0.0000',
            'total_repayable' => '12000.0000',
            'installment_count' => 3,
            'first_due_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'installment_frequency' => 'monthly',
            'currency_code' => 'PKR',
            'requested_by_id' => User::factory(),
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
