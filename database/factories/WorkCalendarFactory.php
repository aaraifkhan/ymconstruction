<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WorkCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkCalendar>
 */
class WorkCalendarFactory extends Factory
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
            'code' => fake()->unique()->bothify('CAL-###'),
            'name' => fake()->words(2, true),
            'timezone' => 'Asia/Karachi',
            'working_weekdays' => [1, 2, 3, 4, 5, 6],
            'effective_from' => now()->startOfYear(),
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
