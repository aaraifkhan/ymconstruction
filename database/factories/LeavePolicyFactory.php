<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeavePolicy>
 */
class LeavePolicyFactory extends Factory
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
            'leave_type_id' => fn (array $attributes) => LeaveType::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'name' => fake()->unique()->words(3, true),
            'effective_from' => now()->startOfYear(),
            'annual_units' => 12,
            'allow_negative_balance' => false,
            'allow_encashment' => false,
            'is_active' => true,
        ];
    }
}
