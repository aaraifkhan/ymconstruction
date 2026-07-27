<?php

namespace Database\Factories;

use App\Enums\ProcurementApprovalStatus;
use App\Models\ProcurementApprovalStep;
use App\Models\PurchaseRequisition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementApprovalStep>
 */
class ProcurementApprovalStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approvable_type' => PurchaseRequisition::class,
            'approvable_id' => PurchaseRequisition::factory(),
            'company_id' => fn (array $attributes): int => PurchaseRequisition::query()
                ->findOrFail($attributes['approvable_id'])->company_id,
            'procurement_approval_rule_id' => null,
            'approval_round' => 1,
            'step_number' => 1,
            'name' => 'Finance Approval',
            'permission_name' => 'Approve:PurchaseRequisition',
            'status' => ProcurementApprovalStatus::Pending,
            'decided_by_id' => null,
            'decided_at' => null,
            'decision_reason' => null,
        ];
    }
}
