<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employment;
use App\Models\ShiftAssignment;
use App\Models\WorkCalendar;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftAssignment>
 */
class ShiftAssignmentFactory extends Factory
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
            'employment_id' => fn (array $attributes) => Employment::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'work_calendar_id' => fn (array $attributes) => WorkCalendar::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'work_shift_id' => fn (array $attributes) => WorkShift::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'effective_from' => now()->startOfYear(),
        ];
    }
}
