<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\OpeningBalanceBatch;
use App\Models\OpeningBalanceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningBalanceLine>
 */
class OpeningBalanceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'line_number' => 1,
            'debit' => 1,
            'credit' => 0,
        ];
    }

    public function forBatchAndAccount(OpeningBalanceBatch $batch, Account $account): static
    {
        return $this->state(fn (): array => [
            'opening_balance_batch_id' => $batch->getKey(),
            'company_id' => $batch->company_id,
            'account_id' => $account->getKey(),
        ]);
    }
}
