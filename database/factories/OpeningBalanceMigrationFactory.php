<?php

namespace Database\Factories;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Models\FinancialPeriod;
use App\Models\OpeningBalanceMigration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningBalanceMigration>
 */
class OpeningBalanceMigrationFactory extends Factory
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
            'source_filename' => 'opening-balances.csv',
            'source_checksum' => hash('sha256', fake()->uuid()),
            'status' => OpeningBalanceMigrationStatus::Draft,
        ];
    }

    public function forPeriod(FinancialPeriod $period, User $preparer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $period->company_id,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'opening_date' => $period->starts_on,
            'prepared_by_id' => $preparer->getKey(),
        ]);
    }
}
