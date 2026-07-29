<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\WorkCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyHoliday>
 */
class CompanyHolidayFactory extends Factory
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
            'work_calendar_id' => fn (array $attributes) => WorkCalendar::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'name' => fake()->words(2, true),
            'holiday_date' => fake()->unique()->dateTimeBetween('now', '+1 year'),
            'is_paid' => true,
            'is_active' => true,
        ];
    }
}
