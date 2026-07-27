<?php

namespace Database\Factories;

use App\Models\BankStatementLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankStatementLine>
 */
class BankStatementLineFactory extends Factory
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
            'transaction_date' => today(),
            'description' => fake()->sentence(),
            'debit' => '0.0000',
            'credit' => '100.0000',
            'balance' => '100.0000',
            'fingerprint' => hash('sha256', fake()->uuid()),
        ];
    }
}
