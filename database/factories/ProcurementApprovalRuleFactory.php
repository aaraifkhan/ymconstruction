<?php

namespace Database\Factories;

use App\Enums\ProcurementDocumentType;
use App\Models\Company;
use App\Models\ProcurementApprovalRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Permission;

/**
 * @extends Factory<ProcurementApprovalRule>
 */
class ProcurementApprovalRuleFactory extends Factory
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
            'document_type' => ProcurementDocumentType::PurchaseRequisition,
            'step_number' => 1,
            'name' => 'Finance Approval',
            'minimum_amount' => null,
            'maximum_amount' => null,
            'permission_name' => fn (): string => Permission::findOrCreate('Approve:PurchaseRequisition', 'web')->name,
            'is_active' => true,
        ];
    }
}
