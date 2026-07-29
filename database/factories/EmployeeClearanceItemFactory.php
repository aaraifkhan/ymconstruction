<?php

namespace Database\Factories;

use App\Enums\ClearanceArea;
use App\Enums\ClearanceSourceKind;
use App\Enums\EmployeeClearanceItemStatus;
use App\Models\Company;
use App\Models\EmployeeClearance;
use App\Models\EmployeeClearanceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeClearanceItem>
 */
class EmployeeClearanceItemFactory extends Factory
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
            'employee_clearance_id' => fn (array $attributes) => EmployeeClearance::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'source_kind' => ClearanceSourceKind::Configured,
            'source_key' => fake()->unique()->bothify('configured:###'),
            'area' => ClearanceArea::Hr,
            'name' => fake()->sentence(3),
            'is_mandatory' => true,
            'status' => EmployeeClearanceItemStatus::Pending,
            'obligation_snapshot' => [],
        ];
    }
}
