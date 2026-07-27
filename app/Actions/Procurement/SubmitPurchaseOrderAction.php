<?php

namespace App\Actions\Procurement;

use App\Enums\ProcurementDocumentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitPurchaseOrderAction
{
    public function __construct(
        private ValidatePurchaseOrderAction $validator,
        private ReserveProcurementNumberAction $numbering,
        private BuildProcurementApprovalStepsAction $approvalSteps,
    ) {}

    public function handle(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('submit', $order);

        return DB::transaction(function () use ($actor, $order): PurchaseOrder {
            $order = PurchaseOrder::query()->whereKey($order)->lockForUpdate()->firstOrFail();
            if (! $order->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected purchase orders may be submitted.']);
            }

            $totals = $this->validator->handle($order);
            $approvalRound = $order->approval_round + 1;
            $number = $order->purchase_order_number ?? $this->numbering->handle(
                $order->company,
                ProcurementDocumentType::PurchaseOrder,
                (int) $order->order_date->year,
            );

            $order->update([
                'status' => PurchaseOrderStatus::Submitted,
                'approval_round' => $approvalRound,
                'purchase_order_number' => $number,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['taxTotal'],
                'grand_total' => $totals['grandTotal'],
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->approvalSteps->handle(
                $order,
                ProcurementDocumentType::PurchaseOrder,
                $totals['grandTotal'],
                $approvalRound,
            );

            activity('purchase_orders')->causedBy($actor)->performedOn($order)->event('submitted')
                ->withProperties(['company_id' => $order->company_id, ...$totals, 'approval_round' => $approvalRound])
                ->log('submitted purchase order');

            return $order->refresh();
        });
    }
}
