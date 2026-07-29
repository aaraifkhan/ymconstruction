<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
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
            'asset_category_id' => fn (array $attributes) => AssetCategory::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'asset_number' => fake()->unique()->bothify('FA-#####'),
            'name' => fake()->words(3, true),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'location' => 'Main Office',
            'acquired_on' => now()->subMonth()->toDateString(),
            'available_for_use_on' => now()->subMonth()->toDateString(),
            'acquisition_cost' => '100000.0000',
            'residual_value' => '10000.0000',
            'useful_life_months' => 36,
            'status' => AssetStatus::Active,
            'prepared_by_id' => User::factory(),
        ];
    }
}
