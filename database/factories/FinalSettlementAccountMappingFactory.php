<?php

namespace Database\Factories;

use App\Enums\FinalSettlementComponentType;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinalSettlementAccountMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinalSettlementAccountMapping> */
class FinalSettlementAccountMappingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'component_type' => FinalSettlementComponentType::Salary,
            'account_id' => fn (array $attributes) => Account::query()
                ->where('company_id', $attributes['company_id'])->where('allows_manual_posting', true)->value('id'),
            'is_active' => true,
        ];
    }
}
