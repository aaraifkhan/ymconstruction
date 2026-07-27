<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class IssuePurchaseOrderAction
{
    public function handle(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('issue', $order);

        return DB::transaction(function () use ($actor, $order): PurchaseOrder {
            $order = PurchaseOrder::query()->whereKey($order)->lockForUpdate()->firstOrFail();
            if ($order->status !== PurchaseOrderStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only an approved purchase order may be issued.']);
            }

            $orderLines = $order->lines()->orderBy('line_number')->lockForUpdate()->get();
            if ($orderLines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'The purchase order has no lines.']);
            }

            foreach ($orderLines as $orderLine) {
                if ($orderLine->purchase_requisition_line_id === null) {
                    continue;
                }

                $requisitionLine = PurchaseRequisitionLine::query()
                    ->whereKey($orderLine->purchase_requisition_line_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $newOrderedQuantity = bcadd(
                    (string) $requisitionLine->ordered_quantity,
                    (string) $orderLine->quantity,
                    4,
                );

                if (bccomp($newOrderedQuantity, (string) $requisitionLine->quantity, 4) === 1) {
                    throw ValidationException::withMessages([
                        'quantity' => "Purchase-order line {$orderLine->line_number} exceeds the remaining requisition quantity.",
                    ]);
                }

                $requisitionLine->update(['ordered_quantity' => $newOrderedQuantity]);
            }

            $order->update([
                'status' => PurchaseOrderStatus::Ordered,
                'ordered_by_id' => $actor->getKey(),
                'ordered_at' => now(),
            ]);

            if ($order->purchase_requisition_id !== null) {
                $this->refreshRequisitionStatus($order->purchase_requisition_id);
            }

            activity('purchase_orders')->causedBy($actor)->performedOn($order)->event('issued')
                ->withProperties([
                    'company_id' => $order->company_id,
                    'purchase_requisition_id' => $order->purchase_requisition_id,
                ])->log('issued approved purchase order');

            return $order->refresh();
        });
    }

    private function refreshRequisitionStatus(int $requisitionId): void
    {
        $requisition = PurchaseRequisition::query()->whereKey($requisitionId)->lockForUpdate()->firstOrFail();
        $lines = $requisition->lines()->lockForUpdate()->get();
        $fullyOrdered = $lines->every(
            fn ($line): bool => bccomp((string) $line->ordered_quantity, (string) $line->quantity, 4) === 0,
        );

        $requisition->update([
            'status' => $fullyOrdered
                ? PurchaseRequisitionStatus::Ordered
                : PurchaseRequisitionStatus::PartiallyOrdered,
        ]);
    }
}
