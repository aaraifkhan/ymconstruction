<?php

namespace Database\Factories;

use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchStatus;
use App\Models\AttendancePunch;
use App\Models\Company;
use App\Models\Employment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendancePunch>
 */
class AttendancePunchFactory extends Factory
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
            'punched_at' => fake()->dateTime(),
            'direction' => AttendancePunchDirection::In,
            'status' => AttendancePunchStatus::Pending,
            'reason' => fake()->sentence(),
            'created_by_id' => User::factory(),
        ];
    }
}
