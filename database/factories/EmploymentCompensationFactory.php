<?php

namespace Database\Factories;

use App\Enums\CompensationStatus;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentCompensation>
 */
class EmploymentCompensationFactory extends Factory
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
            'company_id' => fn (array $attributes): int => Employment::query()
                ->findOrFail($attributes['employment_id'])
                ->company_id,
            'status' => CompensationStatus::Draft,
            'effective_from' => today()->startOfMonth(),
            'effective_to' => null,
            'basic_salary' => '100000.00',
            'house_travel_allowance' => '15000.00',
            'food_allowance' => '10000.00',
            'other_allowance' => '0.00',
            'currency_code' => 'PKR',
            'created_by_id' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => CompensationStatus::Approved,
            'approved_by_id' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
