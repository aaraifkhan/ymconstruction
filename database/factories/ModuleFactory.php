<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($name, '_'),
            'name' => Str::title($name),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
