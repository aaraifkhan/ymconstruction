<?php

namespace Database\Factories;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_type' => VoucherType::Journal,
            'idempotency_key' => fake()->uuid(),
            'status' => JournalStatus::Draft,
            'transaction_date' => today(),
            'description' => fake()->sentence(),
            'currency_code' => 'PKR',
        ];
    }

    public function forPeriod(Company $company, FinancialPeriod $period, User $preparer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'transaction_date' => $period->starts_on,
            'prepared_by_id' => $preparer->getKey(),
        ]);
    }
}
