<?php

namespace Database\Factories;

use App\Enums\EmploymentCategory;
use App\Enums\EmploymentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employment>
 */
class EmploymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'employee_code' => 'EMP-'.fake()->unique()->numerify('#####'),
            'joining_date' => fake()->dateTimeBetween('-8 years', 'now'),
            'employment_category' => fake()->randomElement(EmploymentCategory::cases()),
            'employment_status' => EmploymentStatus::Active,
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
            'working_days_per_week' => 6,
            'appointment_letter_issued' => false,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }
}
