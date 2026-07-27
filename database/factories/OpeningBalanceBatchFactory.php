<?php

namespace Database\Factories;

use App\Enums\OpeningBalanceStatus;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\OpeningBalanceBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningBalanceBatch>
 */
class OpeningBalanceBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_name' => fake()->words(3, true),
            'idempotency_key' => fake()->uuid(),
            'status' => OpeningBalanceStatus::Draft,
        ];
    }

    public function forPeriod(Company $company, FinancialPeriod $period, User $preparer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'opening_date' => $period->starts_on,
            'prepared_by_id' => $preparer->getKey(),
        ]);
    }
}
