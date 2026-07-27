<?php

namespace Database\Factories;

use App\Enums\InventoryTransactionStatus;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use App\Models\ProjectSite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'destination_site_id' => ProjectSite::factory(),
            'company_id' => fn (array $attributes): int => ProjectSite::query()
                ->findOrFail($attributes['destination_site_id'])->company_id,
            'transaction_number' => null,
            'type' => InventoryTransactionType::AdjustmentIncrease,
            'status' => InventoryTransactionStatus::Draft,
            'transaction_date' => today(),
            'source_site_id' => null,
            'project_id' => null,
            'goods_receipt_id' => null,
            'reference' => fake()->optional()->bothify('REF-####'),
            'reason' => fake()->sentence(),
            'prepared_by_id' => User::factory(),
            'total_value' => 0,
        ];
    }
}
