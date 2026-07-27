<?php

namespace App\Actions\Procurement;

use App\Enums\ProcurementApprovalStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectProcurementDocumentAction
{
    public function handle(
        PurchaseRequisition|PurchaseOrder $document,
        User $actor,
        string $reason,
    ): PurchaseRequisition|PurchaseOrder {
        Gate::forUser($actor)->authorize('reject', $document);

        return DB::transaction(function () use ($actor, $document, $reason): PurchaseRequisition|PurchaseOrder {
            $document = $document::query()->whereKey($document)->lockForUpdate()->firstOrFail();
            $submittedStatus = $document instanceof PurchaseRequisition
                ? PurchaseRequisitionStatus::Submitted
                : PurchaseOrderStatus::Submitted;

            if ($document->status !== $submittedStatus || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'Only a submitted procurement document may be rejected with a reason.']);
            }

            $steps = $document->approvalSteps()
                ->where('approval_round', $document->approval_round)
                ->orderBy('step_number')
                ->lockForUpdate()
                ->get();
            $pendingStep = $steps->firstWhere('status', ProcurementApprovalStatus::Pending);

            if ($pendingStep === null) {
                throw ValidationException::withMessages(['approval' => 'No pending approval step exists.']);
            }

            if (! $actor->hasRole('super_admin') && ! $actor->can($pendingStep->permission_name)) {
                throw ValidationException::withMessages(['approval' => "This step requires {$pendingStep->permission_name}."]);
            }

            $pendingStep->update([
                'status' => ProcurementApprovalStatus::Rejected,
                'decided_by_id' => $actor->getKey(),
                'decided_at' => now(),
                'decision_reason' => $reason,
            ]);

            $document->approvalSteps()
                ->where('approval_round', $document->approval_round)
                ->where('status', ProcurementApprovalStatus::Pending)
                ->update([
                    'status' => ProcurementApprovalStatus::Cancelled,
                    'decision_reason' => 'Cancelled after an earlier approval step was rejected.',
                    'updated_at' => now(),
                ]);

            $document->update([
                'status' => $document instanceof PurchaseRequisition
                    ? PurchaseRequisitionStatus::Rejected
                    : PurchaseOrderStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            activity($document instanceof PurchaseOrder ? 'purchase_orders' : 'purchase_requisitions')
                ->causedBy($actor)->performedOn($document)->event('rejected')
                ->withProperties([
                    'company_id' => $document->company_id,
                    'approval_round' => $document->approval_round,
                    'step_number' => $pendingStep->step_number,
                    'reason' => $reason,
                ])->log('rejected procurement document');

            return $document->refresh();
        });
    }
}
