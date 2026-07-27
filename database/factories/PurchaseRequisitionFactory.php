<?php

namespace Database\Factories;

use App\Enums\PurchaseRequisitionStatus;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequisition>
 */
class PurchaseRequisitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'company_id' => fn (array $attributes): int => Project::query()->findOrFail($attributes['project_id'])->company_id,
            'project_site_id' => fn (array $attributes): int => ProjectSite::factory()->create([
                'company_id' => $attributes['company_id'],
                'project_id' => $attributes['project_id'],
            ])->getKey(),
            'requisition_number' => null,
            'required_date' => today()->addWeek(),
            'status' => PurchaseRequisitionStatus::Draft,
            'approval_round' => 0,
            'currency_code' => 'PKR',
            'reason' => fake()->sentence(),
            'estimated_total' => 0,
            'budget_check_status' => 'not_checked',
            'budget_check_snapshot' => null,
            'prepared_by_id' => User::factory(),
            'submitted_by_id' => null,
            'submitted_at' => null,
            'approved_by_id' => null,
            'approved_at' => null,
            'rejected_by_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_by_id' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }
}
