<?php

namespace Database\Factories;

use App\Enums\YearEndClosingStatus;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\User;
use App\Models\YearEndClosing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YearEndClosing>
 */
class YearEndClosingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idempotency_key' => fake()->uuid(),
            'status' => YearEndClosingStatus::Draft,
            'profit_or_loss' => 0,
            'calculation_checksum' => hash('sha256', 'empty'),
            'calculation_snapshot' => [],
        ];
    }

    public function forYear(FinancialYear $year, Account $retainedEarnings, User $preparer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $year->company_id,
            'financial_year_id' => $year->getKey(),
            'retained_earnings_account_id' => $retainedEarnings->getKey(),
            'prepared_by_id' => $preparer->getKey(),
        ]);
    }
}
