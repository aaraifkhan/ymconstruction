<?php

namespace Database\Factories;

use App\Models\ApMatchingSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApMatchingSetting>
 */
class ApMatchingSettingFactory extends Factory
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
            'quantity_tolerance_percentage' => 0,
            'rate_tolerance_percentage' => 0,
            'tax_tolerance_percentage' => 0,
            'is_active' => true,
        ];
    }
}
