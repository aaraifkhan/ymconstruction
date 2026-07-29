<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AssetCategory;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
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
            'code' => fake()->unique()->bothify('ASSET-###'),
            'name' => fake()->words(2, true),
            'cost_account_id' => fn (array $attributes) => Account::factory()->create([
                'company_id' => $attributes['company_id'],
                'account_type' => AccountType::Asset,
                'reporting_group' => AccountType::Asset->value,
            ]),
            'default_useful_life_months' => null,
            'is_depreciable' => false,
            'is_active' => true,
        ];
    }
}
