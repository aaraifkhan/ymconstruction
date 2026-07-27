<?php

namespace Database\Factories;

use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreasuryTransaction>
 */
class TreasuryTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => TreasuryTransactionType::Payment,
            'purpose' => TreasuryPurpose::Other,
            'transaction_date' => today(),
            'status' => TreasuryStatus::Draft,
            'currency_code' => 'PKR',
            'amount' => '1000.0000',
            'description' => fake()->sentence(),
            'prepared_by_id' => User::factory(),
        ];
    }

    public function paymentFrom(Company $company, Account $account, ?CompanyBankAccount $bankAccount = null): static
    {
        return $this->state([
            'company_id' => $company,
            'type' => TreasuryTransactionType::Payment,
            'source_account_id' => $account,
            'source_company_bank_account_id' => $bankAccount,
        ]);
    }
}
