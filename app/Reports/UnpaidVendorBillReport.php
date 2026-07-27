<?php

namespace App\Reports;

use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\Company;
use App\Models\VendorBill;
use Illuminate\Support\Collection;

class UnpaidVendorBillReport
{
    /** @return Collection<int, array{bill:VendorBill, credit_notes:string, open_amount:string}> */
    public function forCompany(Company $company): Collection
    {
        return VendorBill::query()
            ->whereBelongsTo($company)
            ->where('type', VendorBillType::Invoice->value)
            ->where('status', VendorBillStatus::Posted->value)
            ->with('vendor:id,name')
            ->orderBy('due_date')
            ->get()
            ->map(function (VendorBill $bill): array {
                $credits = $bill->postedCreditAmount();

                return [
                    'bill' => $bill,
                    'credit_notes' => $credits,
                    'open_amount' => $bill->postedOpenAmount(),
                ];
            })
            ->filter(fn (array $row): bool => bccomp($row['open_amount'], '0', 4) === 1)
            ->values();
    }
}
