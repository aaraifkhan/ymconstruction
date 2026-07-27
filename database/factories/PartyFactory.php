<?php

namespace Database\Factories;

use App\Enums\PartyRole;
use App\Models\Company;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
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
            'code' => 'PTY-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company(),
            'legal_name' => fake()->company(),
            'roles' => [PartyRole::Customer->value],
            'tax_number' => null,
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'payment_terms_days' => 30,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => ['company_id' => $company]);
    }

    public function withRoles(PartyRole ...$roles): static
    {
        return $this->state(fn (): array => [
            'roles' => collect($roles)->map->value->all(),
        ]);
    }
}
