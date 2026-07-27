<?php

namespace Database\Factories;

use App\Enums\VendorBillDeductionType;
use App\Models\VendorBill;
use App\Models\VendorBillDeduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBillDeduction>
 */
class VendorBillDeductionFactory extends Factory
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
            'type' => VendorBillDeductionType::Retention,
            'description' => 'Retention',
            'rate_snapshot' => 0,
            'amount' => 10,
        ];
    }
}
