<?php

namespace Database\Factories;

use App\Enums\PayrollRunStatus;
use App\Models\Company;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
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
            'reference_number' => 'PAY-'.fake()->unique()->numerify('######'),
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'status' => PayrollRunStatus::Draft,
            'currency_code' => 'PKR',
            'created_by_id' => User::factory(),
        ];
    }
}
