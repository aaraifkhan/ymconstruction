<?php

namespace Database\Factories;

use App\Enums\PayrollVariableComponentStatus;
use App\Enums\PayrollVariableComponentType;
use App\Models\Employment;
use App\Models\PayrollVariableComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollVariableComponent>
 */
class PayrollVariableComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employment_id' => Employment::factory(),
            'company_id' => fn (array $attributes) => Employment::query()
                ->findOrFail($attributes['employment_id'])->company_id,
            'type' => PayrollVariableComponentType::Bonus,
            'status' => PayrollVariableComponentStatus::Draft,
            'earning_period_start' => '2026-07-01',
            'earning_period_end' => '2026-07-31',
            'amount' => 5000,
            'source_reference' => 'BON-'.fake()->unique()->numerify('######'),
        ];
    }

    public function forEmployment(Employment $employment): static
    {
        return $this->state(fn (): array => [
            'company_id' => $employment->company_id,
            'employment_id' => $employment->getKey(),
        ]);
    }
}
