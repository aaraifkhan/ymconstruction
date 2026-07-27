<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\PartyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartyContact>
 */
class PartyContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'company_id' => fn (array $attributes): int => Party::query()
                ->findOrFail($attributes['party_id'])
                ->company_id,
            'name' => fake()->name(),
            'designation' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'is_primary' => false,
            'is_active' => true,
        ];
    }
}
