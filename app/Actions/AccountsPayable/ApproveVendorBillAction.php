<?php

namespace App\Actions\AccountsPayable;

use App\Enums\VendorBillStatus;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveVendorBillAction
{
    public function handle(VendorBill $bill, User $actor): VendorBill
    {
        Gate::forUser($actor)->authorize('approve', $bill);

        return DB::transaction(function () use ($actor, $bill): VendorBill {
            $bill = VendorBill::query()->whereKey($bill)->lockForUpdate()->firstOrFail();
            if ($bill->status !== VendorBillStatus::Reviewed) {
                throw ValidationException::withMessages(['status' => 'Only a reviewed Vendor Bill may be approved.']);
            }
            if (in_array((int) $actor->getKey(), [(int) $bill->prepared_by_id, (int) $bill->reviewed_by_id], true)) {
                throw ValidationException::withMessages(['approved_by_id' => 'Preparation, matching review, and approval require separate actors.']);
            }

            $bill->update([
                'status' => VendorBillStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);

            activity('vendor_bills')->causedBy($actor)->performedOn($bill)->event('approved')
                ->withProperties([
                    'company_id' => $bill->company_id,
                    'gross_total' => $bill->gross_total,
                    'net_payable' => $bill->net_payable,
                    'match_snapshot_hash' => $bill->match_snapshot_hash,
                ])->log('approved Vendor Bill');

            return $bill->refresh();
        }, attempts: 3);
    }
}
