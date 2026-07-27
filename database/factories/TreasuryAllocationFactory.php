<?php

namespace Database\Factories;

use App\Enums\TreasuryAllocationType;
use App\Models\TreasuryAllocation;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreasuryAllocation>
 */
class TreasuryAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'allocatable_type' => VendorBill::class,
            'allocation_type' => TreasuryAllocationType::VendorBill,
            'amount' => '100.0000',
        ];
    }
}
