<?php

namespace Database\Factories;

use App\Enums\CompanyModuleState;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyModule>
 */
class CompanyModuleFactory extends Factory
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
            'module_id' => Module::factory(),
            'state' => CompanyModuleState::Inherit,
            'variant' => null,
            'settings' => null,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (): array => ['state' => CompanyModuleState::Enabled]);
    }
}
