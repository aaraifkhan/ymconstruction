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

class CancelPurchaseOrderAction
{
    public function handle(PurchaseOrder $order, User $actor, string $reason): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('cancel', $order);

        return DB::transaction(function () use ($actor, $order, $reason): PurchaseOrder {
            $order = PurchaseOrder::query()->whereKey($order)->lockForUpdate()->firstOrFail();
            if (! in_array($order->status, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::Rejected,
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Ordered,
            ], true) || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'This purchase order cannot be cancelled, or a cancellation reason is missing.']);
            }

            $lines = $order->lines()->lockForUpdate()->get();
            if ($lines->contains(fn ($line): bool => bccomp((string) $line->received_quantity, '0', 4) === 1)) {
                throw ValidationException::withMessages(['status' => 'A purchase order with received quantity cannot be cancelled.']);
            }

            if ($order->status === PurchaseOrderStatus::Ordered) {
                foreach ($lines as $line) {
                    if ($line->purchase_requisition_line_id === null) {
                        continue;
                    }

                    $requisitionLine = PurchaseRequisitionLine::query()
                        ->whereKey($line->purchase_requisition_line_id)->lockForUpdate()->firstOrFail();
                    $newOrderedQuantity = bcsub(
                        (string) $requisitionLine->ordered_quantity,
                        (string) $line->quantity,
                        4,
                    );

                    if (bccomp($newOrderedQuantity, '0', 4) === -1) {
                        throw ValidationException::withMessages(['quantity' => 'Requisition ordered quantity is inconsistent with this purchase order.']);
                    }

                    $requisitionLine->update(['ordered_quantity' => $newOrderedQuantity]);
                }
            }

            $order->update([
                'status' => PurchaseOrderStatus::Cancelled,
                'cancelled_by_id' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if ($order->purchase_requisition_id !== null) {
                $this->refreshRequisitionStatus($order->purchase_requisition_id);
            }

            activity('purchase_orders')->causedBy($actor)->performedOn($order)->event('cancelled')
                ->withProperties(['company_id' => $order->company_id, 'reason' => $reason])
                ->log('cancelled purchase order');

            return $order->refresh();
        });
    }

    private function refreshRequisitionStatus(int $requisitionId): void
    {
        $requisition = PurchaseRequisition::query()->whereKey($requisitionId)->lockForUpdate()->firstOrFail();
        $lines = $requisition->lines()->lockForUpdate()->get();
        $hasOrderedQuantity = $lines->contains(
            fn ($line): bool => bccomp((string) $line->ordered_quantity, '0', 4) === 1,
        );
        $fullyOrdered = $hasOrderedQuantity && $lines->every(
            fn ($line): bool => bccomp((string) $line->ordered_quantity, (string) $line->quantity, 4) === 0,
        );

        $requisition->update([
            'status' => match (true) {
                $fullyOrdered => PurchaseRequisitionStatus::Ordered,
                $hasOrderedQuantity => PurchaseRequisitionStatus::PartiallyOrdered,
                default => PurchaseRequisitionStatus::Approved,
            },
        ]);
    }
}
