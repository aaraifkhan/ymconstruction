<?php

namespace Database\Factories;

use App\Models\PurchaseOrderLine;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBillLine>
 */
class VendorBillLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_bill_id' => VendorBill::factory(),
            'company_id' => fn (array $attributes): int => VendorBill::query()
                ->findOrFail($attributes['vendor_bill_id'])->company_id,
            'purchase_order_line_id' => fn (array $attributes): int => PurchaseOrderLine::query()
                ->where('purchase_order_id', VendorBill::query()
                    ->findOrFail($attributes['vendor_bill_id'])->purchase_order_id)
                ->firstOrFail()
                ->getKey(),
            'item_id' => fn (array $attributes): int => PurchaseOrderLine::query()
                ->findOrFail($attributes['purchase_order_line_id'])->item_id,
            'unit_of_measure_id' => fn (array $attributes): int => PurchaseOrderLine::query()
                ->findOrFail($attributes['purchase_order_line_id'])->unit_of_measure_id,
            'line_number' => 1,
            'item_name_snapshot' => 'Pending',
            'quantity' => 10,
            'unit_rate' => 100,
            'line_subtotal' => 1000,
            'tax_amount' => 0,
            'line_total' => 1000,
        ];
    }
}
