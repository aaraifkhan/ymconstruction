<?php

namespace App\Actions\Procurement;

use App\Enums\ProcurementDocumentType;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitPurchaseRequisitionAction
{
    public function __construct(
        private ValidatePurchaseRequisitionAction $validator,
        private ReserveProcurementNumberAction $numbering,
        private BuildProcurementApprovalStepsAction $approvalSteps,
    ) {}

    public function handle(PurchaseRequisition $requisition, User $actor): PurchaseRequisition
    {
        Gate::forUser($actor)->authorize('submit', $requisition);

        return DB::transaction(function () use ($actor, $requisition): PurchaseRequisition {
            $requisition = PurchaseRequisition::query()->whereKey($requisition)->lockForUpdate()->firstOrFail();
            if (! $requisition->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected requisitions may be submitted.']);
            }

            $validation = $this->validator->handle($requisition);
            $approvalRound = $requisition->approval_round + 1;
            $number = $requisition->requisition_number ?? $this->numbering->handle(
                $requisition->company,
                ProcurementDocumentType::PurchaseRequisition,
                (int) $requisition->required_date->year,
            );

            $requisition->update([
                ...$validation,
                'status' => PurchaseRequisitionStatus::Submitted,
                'approval_round' => $approvalRound,
                'requisition_number' => $number,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->approvalSteps->handle(
                $requisition,
                ProcurementDocumentType::PurchaseRequisition,
                $validation['estimated_total'],
                $approvalRound,
            );

            activity('purchase_requisitions')->causedBy($actor)->performedOn($requisition)->event('submitted')
                ->withProperties(['company_id' => $requisition->company_id, ...$validation, 'approval_round' => $approvalRound])
                ->log('submitted purchase requisition');

            return $requisition->refresh();
        });
    }
}
