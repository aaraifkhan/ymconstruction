<?php

namespace Database\Factories;

use App\Enums\BankStatementStatus;
use App\Models\BankStatement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankStatement>
 */
class BankStatementFactory extends Factory
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
            'opening_balance' => '0.0000',
            'closing_balance' => '0.0000',
            'currency_code' => 'PKR',
            'status' => BankStatementStatus::Draft,
        ];
    }
}
