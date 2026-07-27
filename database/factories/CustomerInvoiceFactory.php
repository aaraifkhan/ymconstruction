<?php

namespace Database\Factories;

use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\PartyRole;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInvoice>
 */
class CustomerInvoiceFactory extends Factory
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
            'customer_id' => fn (array $attributes) => Party::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id']))
                ->withRoles(PartyRole::Customer),
            'type' => CustomerInvoiceType::Invoice,
            'category' => CustomerInvoiceCategory::ServiceInvoice,
            'invoice_date' => '2026-07-15',
            'due_date' => '2026-08-14',
            'currency_code' => 'PKR',
            'status' => CustomerInvoiceStatus::Draft,
            'description' => fake()->sentence(),
            'prepared_by_id' => User::factory(),
        ];
    }
}
