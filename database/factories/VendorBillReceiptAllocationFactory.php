<?php

namespace Database\Factories;

use App\Models\GoodsReceiptLine;
use App\Models\VendorBillLine;
use App\Models\VendorBillReceiptAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBillReceiptAllocation>
 */
class VendorBillReceiptAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_bill_line_id' => VendorBillLine::factory(),
            'goods_receipt_line_id' => GoodsReceiptLine::factory(),
            'company_id' => fn (array $attributes): int => VendorBillLine::query()
                ->findOrFail($attributes['vendor_bill_line_id'])->company_id,
            'quantity' => 1,
            'receipt_unit_cost' => 0,
            'receipt_value' => 0,
        ];
    }
}
