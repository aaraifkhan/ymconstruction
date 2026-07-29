<?php

namespace Database\Factories;

use App\Enums\AssetCustodyEventType;
use App\Models\Company;
use App\Models\EmployeeAssetCustody;
use App\Models\EmployeeAssetCustodyEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAssetCustodyEvent>
 */
class EmployeeAssetCustodyEventFactory extends Factory
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
            'employee_asset_custody_id' => fn (array $attributes) => EmployeeAssetCustody::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'fixed_asset_id' => fn (array $attributes) => EmployeeAssetCustody::query()
                ->findOrFail($attributes['employee_asset_custody_id'])->fixed_asset_id,
            'employment_id' => fn (array $attributes) => EmployeeAssetCustody::query()
                ->findOrFail($attributes['employee_asset_custody_id'])->employment_id,
            'event_type' => AssetCustodyEventType::Issued,
            'effective_on' => now()->toDateString(),
            'snapshot' => [],
        ];
    }
}
