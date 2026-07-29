<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkShift>
 */
class WorkShiftFactory extends Factory
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
            'code' => fake()->unique()->bothify('SHIFT-###'),
            'name' => fake()->words(2, true),
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_minutes' => 60,
            'is_overnight' => false,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
