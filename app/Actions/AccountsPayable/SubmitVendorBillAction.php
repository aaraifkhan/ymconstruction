<?php

namespace App\Actions\AccountsPayable;

use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitVendorBillAction
{
    public function __construct(
        private AllocateVendorBillReceiptsAction $allocateReceipts,
        private CalculateVendorBillTotalsAction $calculateTotals,
        private ReserveVendorBillNumberAction $reserveNumber,
    ) {}

    public function handle(VendorBill $bill, User $actor): VendorBill
    {
        Gate::forUser($actor)->authorize('submit', $bill);

        return DB::transaction(function () use ($actor, $bill): VendorBill {
            $bill = VendorBill::query()->whereKey($bill)->lockForUpdate()->firstOrFail();
            if (! $bill->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected Vendor Bills may be submitted.']);
            }
            if ($bill->lines()->count() === 0) {
                throw ValidationException::withMessages(['lines' => 'At least one Vendor Bill line is required.']);
            }
            if ($bill->type === VendorBillType::Invoice) {
                $order = $bill->purchaseOrder()->lockForUpdate()->first();
                if ($order === null || ! in_array($order->status, [
                    PurchaseOrderStatus::Ordered,
                    PurchaseOrderStatus::PartiallyReceived,
                    PurchaseOrderStatus::Received,
                ], true)) {
                    throw ValidationException::withMessages(['purchase_order_id' => 'An issued Purchase Order is required for a Vendor Bill.']);
                }
            } else {
                $original = $bill->originalVendorBill()->lockForUpdate()->first();
                if ($original === null
                    || $original->status !== VendorBillStatus::Posted
                    || (int) $original->company_id !== (int) $bill->company_id
                    || (int) $original->vendor_id !== (int) $bill->vendor_id) {
                    throw ValidationException::withMessages(['original_vendor_bill_id' => 'Credit Note must reference a posted same-company bill for the same vendor.']);
                }
            }

            $this->allocateReceipts->handle($bill);
            $bill = $this->calculateTotals->handle($bill);
            $vendor = $bill->vendor()->firstOrFail();
            $bill->update([
                'vendor_bill_number' => $bill->vendor_bill_number ?? $this->reserveNumber->handle($bill),
                'counterparty_classification' => $vendor->hasRole(PartyRole::Contractor) ? 'contractor' : 'supplier',
                'status' => VendorBillStatus::Submitted,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'reviewed_by_id' => null,
                'reviewed_at' => null,
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('vendor_bills')->causedBy($actor)->performedOn($bill)->event('submitted')
                ->withProperties([
                    'company_id' => $bill->company_id,
                    'vendor_bill_number' => $bill->vendor_bill_number,
                    'gross_total' => $bill->gross_total,
                ])->log('submitted Vendor Bill for matching');

            return $bill->refresh();
        }, attempts: 3);
    }
}
