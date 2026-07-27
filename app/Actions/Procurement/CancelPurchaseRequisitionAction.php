<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelPurchaseRequisitionAction
{
    public function handle(PurchaseRequisition $requisition, User $actor, string $reason): PurchaseRequisition
    {
        Gate::forUser($actor)->authorize('cancel', $requisition);

        return DB::transaction(function () use ($actor, $reason, $requisition): PurchaseRequisition {
            $requisition = PurchaseRequisition::query()->whereKey($requisition)->lockForUpdate()->firstOrFail();

            if (! in_array($requisition->status, [
                PurchaseRequisitionStatus::Draft,
                PurchaseRequisitionStatus::Rejected,
                PurchaseRequisitionStatus::Approved,
            ], true) || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'Only an un-ordered draft, rejected, or approved requisition may be cancelled with a reason.']);
            }

            if ($requisition->lines()->lockForUpdate()->get()->contains(
                fn ($line): bool => bccomp((string) $line->ordered_quantity, '0', 4) === 1,
            )) {
                throw ValidationException::withMessages(['status' => 'A requisition with ordered quantities cannot be cancelled.']);
            }

            $requisition->update([
                'status' => PurchaseRequisitionStatus::Cancelled,
                'cancelled_by_id' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            activity('purchase_requisitions')->causedBy($actor)->performedOn($requisition)->event('cancelled')
                ->withProperties(['company_id' => $requisition->company_id, 'reason' => $reason])
                ->log('cancelled purchase requisition');

            return $requisition->refresh();
        });
    }
}
