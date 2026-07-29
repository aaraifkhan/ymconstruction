<?php

namespace Database\Factories;

use App\Enums\PayrollComponentNature;
use App\Enums\PayrollComponentType;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollEntryComponent>
 */
class PayrollEntryComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_entry_id' => PayrollEntry::factory(),
            'company_id' => fn (array $attributes) => PayrollEntry::query()
                ->findOrFail($attributes['payroll_entry_id'])->company_id,
            'employment_id' => fn (array $attributes) => PayrollEntry::query()
                ->findOrFail($attributes['payroll_entry_id'])->employment_id,
            'type' => PayrollComponentType::PayableBasic,
            'nature' => PayrollComponentNature::Earning,
            'quantity' => 1,
            'rate' => 10000,
            'amount' => 10000,
            'source_checksum' => hash('sha256', fake()->uuid()),
            'evidence_snapshot' => ['factory' => true],
            'idempotency_key' => fake()->uuid(),
        ];
    }
}
