<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => fake()->unique()->numerify('9####'),
            'name' => fake()->words(3, true),
            'account_type' => AccountType::Expense,
            'reporting_group' => AccountType::Expense->value,
            'normal_balance' => NormalBalance::Debit,
            'is_control_account' => false,
            'allows_manual_posting' => true,
            'is_system_generated' => false,
            'is_active' => true,
        ];
    }
}
