<?php

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyBankAccount>
 */
class CompanyBankAccountFactory extends Factory
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
            'bank_name' => fake()->randomElement(['HBL', 'Meezan Bank', 'UBL', 'MCB', 'Bank Alfalah']),
            'branch_name' => fake()->city().' Branch',
            'branch_code' => fake()->numerify('####'),
            'account_title' => fake()->company(),
            'account_number' => fake()->numerify('##############'),
            'iban' => fake()->iban('PK'),
            'swift_code' => fake()->optional()->bothify('????PK??'),
            'currency_code' => 'PKR',
            'account_type' => BankAccountType::Current,
            'is_default_for_payroll' => false,
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
