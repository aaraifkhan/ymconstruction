<?php

namespace Database\Factories;

use App\Enums\LeaveLedgerEntryType;
use App\Models\Company;
use App\Models\Employment;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveLedgerEntry>
 */
class LeaveLedgerEntryFactory extends Factory
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
            'leave_type_id' => fn (array $attributes) => LeaveType::factory()
                ->forCompany(Company::query()->findOrFail($attributes['company_id'])),
            'entry_type' => LeaveLedgerEntryType::Adjustment,
            'effective_on' => now(),
            'units' => 1,
            'reason' => fake()->sentence(),
            'recorded_by_id' => User::factory(),
        ];
    }
}
