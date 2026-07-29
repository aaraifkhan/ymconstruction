<?php

namespace Database\Factories;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveRequestStatus;
use App\Models\Company;
use App\Models\Employment;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
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
            'leave_type_id' => fn (array $attributes) => LeaveType::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeek()->toDateString(),
            'requested_units' => 1,
            'reason' => fake()->sentence(),
            'status' => LeaveRequestStatus::Draft,
            'is_paid_snapshot' => true,
            'payroll_impact_snapshot' => LeavePayrollImpact::None,
        ];
    }
}
