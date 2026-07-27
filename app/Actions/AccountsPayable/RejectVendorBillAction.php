<?php

namespace App\Actions\AccountsPayable;

use App\Enums\VendorBillStatus;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectVendorBillAction
{
    public function handle(VendorBill $bill, User $actor, string $reason): VendorBill
    {
        Gate::forUser($actor)->authorize('reject', $bill);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($actor, $bill, $reason): VendorBill {
            $bill = VendorBill::query()->whereKey($bill)->lockForUpdate()->firstOrFail();
            if (! in_array($bill->status, [VendorBillStatus::Submitted, VendorBillStatus::Reviewed], true)) {
                throw ValidationException::withMessages(['status' => 'Only a submitted or reviewed Vendor Bill may be rejected.']);
            }

            $bill->update([
                'status' => VendorBillStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            activity('vendor_bills')->causedBy($actor)->performedOn($bill)->event('rejected')
                ->withProperties(['company_id' => $bill->company_id, 'reason' => $reason])
                ->log('rejected Vendor Bill');

            return $bill->refresh();
        }, attempts: 3);
    }
}
