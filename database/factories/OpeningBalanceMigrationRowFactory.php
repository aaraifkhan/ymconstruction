<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\OpeningBalanceMigration;
use App\Models\OpeningBalanceMigrationRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningBalanceMigrationRow>
 */
class OpeningBalanceMigrationRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_row_number' => fake()->unique()->numberBetween(2, 100000),
            'account_code' => fake()->numerify('####'),
            'debit' => 1,
            'credit' => 0,
        ];
    }

    public function forMigrationAndAccount(OpeningBalanceMigration $migration, Account $account): static
    {
        return $this->state(fn (): array => [
            'opening_balance_migration_id' => $migration->getKey(),
            'company_id' => $migration->company_id,
            'account_id' => $account->getKey(),
            'account_code' => $account->code,
        ]);
    }
}
