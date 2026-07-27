<?php

namespace Database\Factories;

use App\Models\BankReconciliationMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankReconciliationMatch>
 */
class BankReconciliationMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => '100.0000',
            'matched_by_id' => User::factory(),
            'matched_at' => now(),
        ];
    }
}
