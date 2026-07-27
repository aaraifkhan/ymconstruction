<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CustomerInvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInvoiceLine>
 */
class CustomerInvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'line_number' => fake()->unique()->numberBetween(1, 100000),
            'item_name_snapshot' => fake()->words(3, true),
            'quantity' => '1.0000',
            'unit_rate' => '1000.0000',
            'revenue_account_id' => fn (array $attributes) => Account::factory()->create([
                'company_id' => $attributes['company_id'] ?? Company::factory(),
                'account_type' => AccountType::Revenue,
                'allows_manual_posting' => true,
                'is_active' => true,
            ]),
        ];
    }
}
