<?php

namespace Database\Factories;

use App\Enums\IntercompanyDirection;
use App\Models\Account;
use App\Models\Company;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntercompanyTransaction>
 */
class IntercompanyTransactionFactory extends Factory
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
            'transaction_date' => today(),
            'direction' => IntercompanyDirection::OriginReceivable,
            'amount' => fake()->randomFloat(4, 1, 100000),
            'description' => fake()->sentence(),
        ];
    }

    public function forCompanies(Company $origin, Company $counterparty, Account $originOffset, Account $counterpartyOffset, User $preparer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $origin->getKey(),
            'counterparty_company_id' => $counterparty->getKey(),
            'origin_offset_account_id' => $originOffset->getKey(),
            'counterparty_offset_account_id' => $counterpartyOffset->getKey(),
            'prepared_by_id' => $preparer->getKey(),
        ]);
    }
}
