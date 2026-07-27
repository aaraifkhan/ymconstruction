<?php

namespace Database\Factories;

use App\Enums\CustomerInvoiceAdjustmentType;
use App\Models\CustomerInvoiceAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInvoiceAdjustment>
 */
class CustomerInvoiceAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => CustomerInvoiceAdjustmentType::Retention,
            'description' => 'Contract retention',
            'amount' => '100.0000',
        ];
    }
}
