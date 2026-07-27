<?php

namespace App\Actions\Procurement;

use App\Enums\ProcurementApprovalStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\ProcurementApprovalStep;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveProcurementDocumentAction
{
    public function handle(PurchaseRequisition|PurchaseOrder $document, User $actor): PurchaseRequisition|PurchaseOrder
    {
        Gate::forUser($actor)->authorize('approve', $document);

        return DB::transaction(function () use ($actor, $document): PurchaseRequisition|PurchaseOrder {
            $document = $document::query()->whereKey($document)->lockForUpdate()->firstOrFail();
            $submittedStatus = $document instanceof PurchaseRequisition
                ? PurchaseRequisitionStatus::Submitted
                : PurchaseOrderStatus::Submitted;

            if ($document->status !== $submittedStatus) {
                throw ValidationException::withMessages(['status' => 'Only a submitted procurement document may be approved.']);
            }

            if ((int) $document->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['approved_by_id' => 'The preparer cannot approve the same procurement document.']);
            }

            $steps = $document->approvalSteps()
                ->where('approval_round', $document->approval_round)
                ->orderBy('step_number')
                ->lockForUpdate()
                ->get();
            /** @var ProcurementApprovalStep|null $pendingStep */
            $pendingStep = $steps->firstWhere('status', ProcurementApprovalStatus::Pending);

            if ($pendingStep === null) {
                throw ValidationException::withMessages(['approval' => 'No pending approval step exists.']);
            }

            if (! $actor->hasRole('super_admin') && ! $actor->can($pendingStep->permission_name)) {
                throw ValidationException::withMessages(['approval' => "This step requires {$pendingStep->permission_name}."]);
            }

            $pendingStep->update([
                'status' => ProcurementApprovalStatus::Approved,
                'decided_by_id' => $actor->getKey(),
                'decided_at' => now(),
            ]);

            $hasPendingSteps = $document->approvalSteps()
                ->where('approval_round', $document->approval_round)
                ->where('status', ProcurementApprovalStatus::Pending)
                ->exists();

            if (! $hasPendingSteps) {
                if ($document instanceof PurchaseOrder) {
                    $snapshot = $this->purchaseOrderSnapshot($document);
                    $document->update([
                        'status' => PurchaseOrderStatus::Approved,
                        'approved_by_id' => $actor->getKey(),
                        'approved_at' => now(),
                        'approved_snapshot' => $snapshot,
                        'approved_snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
                    ]);
                } else {
                    $document->update([
                        'status' => PurchaseRequisitionStatus::Approved,
                        'approved_by_id' => $actor->getKey(),
                        'approved_at' => now(),
                    ]);
                }
            }

            activity($document instanceof PurchaseOrder ? 'purchase_orders' : 'purchase_requisitions')
                ->causedBy($actor)->performedOn($document)->event('approval_step_approved')
                ->withProperties([
                    'company_id' => $document->company_id,
                    'approval_round' => $document->approval_round,
                    'step_number' => $pendingStep->step_number,
                    'step_name' => $pendingStep->name,
                    'final_approval' => ! $hasPendingSteps,
                ])->log('approved procurement workflow step');

            return $document->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function purchaseOrderSnapshot(PurchaseOrder $order): array
    {
        $order->loadMissing(['vendor', 'project', 'projectSite', 'lines']);

        return [
            'purchase_order_number' => $order->purchase_order_number,
            'company_id' => $order->company_id,
            'vendor' => ['id' => $order->vendor_id, 'code' => $order->vendor->code, 'name' => $order->vendor->name],
            'project' => ['id' => $order->project_id, 'code' => $order->project->code, 'name' => $order->project->name],
            'project_site' => ['id' => $order->project_site_id, 'code' => $order->projectSite->code, 'name' => $order->projectSite->name],
            'order_date' => $order->order_date->toDateString(),
            'currency_code' => $order->currency_code,
            'payment_terms_days' => $order->payment_terms_days,
            'payment_terms' => $order->payment_terms,
            'subtotal' => $order->subtotal,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'lines' => $order->lines->map(fn ($line): array => [
                'line_number' => $line->line_number,
                'requisition_line_id' => $line->purchase_requisition_line_id,
                'item_id' => $line->item_id,
                'item_code' => $line->item_code_snapshot,
                'item_name' => $line->item_name_snapshot,
                'uom' => $line->uom_snapshot,
                'quantity' => $line->quantity,
                'unit_rate' => $line->unit_rate,
                'tax_code' => $line->tax_code_snapshot,
                'tax_rate' => $line->tax_rate_snapshot,
                'tax_calculation_method' => $line->tax_calculation_method_snapshot,
                'line_subtotal' => $line->line_subtotal,
                'tax_amount' => $line->tax_amount,
                'line_total' => $line->line_total,
                'specification' => $line->specification,
            ])->all(),
        ];
    }
}
