<?php

namespace Database\Factories;

use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use App\Models\Company;
use App\Models\TaxCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxCode>
 */
class TaxCodeFactory extends Factory
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
            'code' => fake()->unique()->bothify('TAX-####'),
            'name' => fake()->words(3, true),
            'type' => TaxCodeType::Other,
            'rate' => fake()->randomFloat(4, 0, 20),
            'calculation_method' => TaxCalculationMethod::Exclusive,
            'effective_from' => today(),
            'effective_to' => null,
            'is_recoverable' => false,
            'is_active' => false,
            'notes' => 'Synthetic test data only.',
        ];
    }
}
