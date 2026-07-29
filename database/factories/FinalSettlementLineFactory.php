<?php

namespace Database\Factories;

use App\Enums\FinalSettlementComponentType;
use App\Models\Account;
use App\Models\FinalSettlement;
use App\Models\FinalSettlementLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinalSettlementLine> */
class FinalSettlementLineFactory extends Factory
{
    public function definition(): array
    {
        $type = FinalSettlementComponentType::Salary;

        return [
            'final_settlement_id' => FinalSettlement::factory(),
            'company_id' => fn (array $attributes) => FinalSettlement::query()
                ->findOrFail($attributes['final_settlement_id'])->company_id,
            'line_number' => 1,
            'component_type' => $type,
            'nature' => $type->nature(),
            'account_id' => fn (array $attributes) => Account::query()
                ->where('company_id', $attributes['company_id'])->where('allows_manual_posting', true)->value('id'),
            'description' => 'Approved settlement component',
            'quantity' => '1.0000',
            'rate' => '10000.0000',
            'amount' => '10000.0000',
            'source_reference' => fake()->uuid(),
            'evidence_snapshot' => ['approved' => true],
            'source_checksum' => fake()->sha256(),
            'idempotency_key' => fake()->uuid(),
        ];
    }
}
