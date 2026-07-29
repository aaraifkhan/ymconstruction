<?php

namespace Database\Factories;

use App\Enums\EmployeeAssetCustodyStatus;
use App\Models\Company;
use App\Models\EmployeeAssetCustody;
use App\Models\Employment;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAssetCustody>
 */
class EmployeeAssetCustodyFactory extends Factory
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
            'fixed_asset_id' => fn (array $attributes) => FixedAsset::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'employment_id' => fn (array $attributes) => Employment::factory()->forCompany(
                Company::query()->findOrFail($attributes['company_id']),
            ),
            'status' => EmployeeAssetCustodyStatus::Draft,
            'issued_on' => now()->toDateString(),
            'issued_condition' => 'Good',
            'accessories' => ['Charger'],
            'issued_location' => 'Main Office',
            'prepared_by_id' => User::factory(),
        ];
    }
}
