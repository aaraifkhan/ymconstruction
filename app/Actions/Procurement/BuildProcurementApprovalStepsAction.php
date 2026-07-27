<?php

namespace App\Actions\Procurement;

use App\Enums\ProcurementDocumentType;
use App\Models\ProcurementApprovalRule;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Illuminate\Support\Collection;

class BuildProcurementApprovalStepsAction
{
    public function handle(
        PurchaseRequisition|PurchaseOrder $document,
        ProcurementDocumentType $documentType,
        string $amount,
        int $approvalRound,
    ): void {
        $rules = ProcurementApprovalRule::query()
            ->where('company_id', $document->company_id)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('minimum_amount')->orWhere('minimum_amount', '<=', $amount))
            ->where(fn ($query) => $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $amount))
            ->orderBy('step_number')
            ->lockForUpdate()
            ->get();

        if ($rules->isEmpty()) {
            $rules = new Collection([
                new ProcurementApprovalRule([
                    'step_number' => 1,
                    'name' => 'Finance Approval',
                    'permission_name' => $documentType->approvalPermission(),
                ]),
            ]);
        }

        foreach ($rules as $rule) {
            $document->approvalSteps()->create([
                'company_id' => $document->company_id,
                'procurement_approval_rule_id' => $rule->exists ? $rule->getKey() : null,
                'approval_round' => $approvalRound,
                'step_number' => $rule->step_number,
                'name' => $rule->name,
                'permission_name' => $rule->permission_name,
            ]);
        }
    }
}
