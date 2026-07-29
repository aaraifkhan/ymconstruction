<?php

namespace Database\Factories;

use App\Enums\ClearanceArea;
use App\Models\ClearanceChecklistTemplate;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClearanceChecklistTemplate>
 */
class ClearanceChecklistTemplateFactory extends Factory
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
            'code' => fake()->unique()->bothify('CHECK-###'),
            'name' => fake()->sentence(3),
            'area' => fake()->randomElement(ClearanceArea::cases()),
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
