<?php

namespace Database\Factories;

use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBill>
 */
class VendorBillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'company_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->company_id,
            'vendor_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->vendor_id,
            'project_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->project_id,
            'project_site_id' => fn (array $attributes): int => PurchaseOrder::query()
                ->findOrFail($attributes['purchase_order_id'])->project_site_id,
            'vendor_bill_number' => null,
            'vendor_invoice_number' => fake()->unique()->bothify('VIN-####'),
            'type' => VendorBillType::Invoice,
            'invoice_date' => today(),
            'due_date' => today()->addDays(30),
            'status' => VendorBillStatus::Draft,
            'currency_code' => 'PKR',
            'prepared_by_id' => User::factory(),
        ];
    }
}
