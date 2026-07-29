<?php

namespace Database\Factories;

use App\Enums\FinalSettlementStatus;
use App\Models\Company;
use App\Models\EmployeeClearance;
use App\Models\Employment;
use App\Models\EmploymentSeparation;
use App\Models\FinalSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinalSettlement> */
class FinalSettlementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employment_id' => fn (array $attributes) => Employment::factory()->forCompany(
                Company::query()->findOrFail($attributes['company_id']),
            ),
            'employment_separation_id' => fn (array $attributes) => EmploymentSeparation::factory()->create([
                'company_id' => $attributes['company_id'],
                'employment_id' => $attributes['employment_id'],
                'approved_last_working_date' => '2026-07-31',
            ]),
            'employee_clearance_id' => fn (array $attributes) => EmployeeClearance::factory()->create([
                'company_id' => $attributes['company_id'],
                'employment_id' => $attributes['employment_id'],
                'employment_separation_id' => $attributes['employment_separation_id'],
            ]),
            'reference_number' => fake()->unique()->bothify('FNS-######'),
            'cutoff_date' => '2026-07-31',
            'status' => FinalSettlementStatus::Draft,
            'earning_total' => '0.0000',
            'recovery_total' => '0.0000',
            'net_amount' => '0.0000',
            'prepared_by_id' => User::factory(),
        ];
    }
}
