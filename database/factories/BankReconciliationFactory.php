<?php

namespace Database\Factories;

use App\Enums\BankReconciliationStatus;
use App\Models\BankReconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankReconciliation>
 */
class BankReconciliationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'status' => BankReconciliationStatus::Draft,
            'statement_closing_balance' => '0.0000',
            'book_closing_balance' => '0.0000',
            'difference' => '0.0000',
            'prepared_by_id' => User::factory(),
        ];
    }
}
