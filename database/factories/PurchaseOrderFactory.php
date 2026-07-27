<?php

namespace Database\Factories;

use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_requisition_id' => PurchaseRequisition::factory(),
            'company_id' => fn (array $attributes): int => PurchaseRequisition::query()
                ->findOrFail($attributes['purchase_requisition_id'])->company_id,
            'vendor_id' => fn (array $attributes): int => Party::factory()->create([
                'company_id' => $attributes['company_id'],
                'roles' => [PartyRole::Vendor->value],
            ])->getKey(),
            'project_id' => fn (array $attributes): int => PurchaseRequisition::query()
                ->findOrFail($attributes['purchase_requisition_id'])->project_id,
            'project_site_id' => fn (array $attributes): int => PurchaseRequisition::query()
                ->findOrFail($attributes['purchase_requisition_id'])->project_site_id,
            'purchase_order_number' => null,
            'order_date' => today(),
            'status' => PurchaseOrderStatus::Draft,
            'approval_round' => 0,
            'currency_code' => 'PKR',
            'payment_terms_days' => 0,
            'payment_terms' => null,
            'notes' => fake()->optional()->sentence(),
            'subtotal' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
            'approved_snapshot' => null,
            'approved_snapshot_hash' => null,
            'prepared_by_id' => User::factory(),
            'submitted_by_id' => null,
            'submitted_at' => null,
            'approved_by_id' => null,
            'approved_at' => null,
            'rejected_by_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'ordered_by_id' => null,
            'ordered_at' => null,
            'cancelled_by_id' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }
}
