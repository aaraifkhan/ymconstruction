<?php

namespace Database\Factories;

use App\Enums\AttendanceSummaryStatus;
use App\Models\AttendanceMonthlySummary;
use App\Models\Company;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceMonthlySummary>
 */
class AttendanceMonthlySummaryFactory extends Factory
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
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => AttendanceSummaryStatus::Draft,
            'source_checksum' => hash('sha256', fake()->uuid()),
        ];
    }
}
