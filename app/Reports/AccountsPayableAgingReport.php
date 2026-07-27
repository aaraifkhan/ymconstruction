<?php

namespace App\Reports;

use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\Company;
use App\Models\VendorBill;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AccountsPayableAgingReport
{
    /** @return array{as_of:string, buckets:array<string, string>, bills:Collection<int, VendorBill>} */
    public function forCompany(Company $company, CarbonInterface $asOf): array
    {
        $bills = VendorBill::query()
            ->whereBelongsTo($company)
            ->where('status', VendorBillStatus::Posted->value)
            ->whereDate('invoice_date', '<=', $asOf)
            ->with('vendor:id,name')
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current' => '0.0000',
            '1_30' => '0.0000',
            '31_60' => '0.0000',
            '61_90' => '0.0000',
            'over_90' => '0.0000',
        ];

        foreach ($bills as $bill) {
            $daysPastDue = (int) max(0, $bill->due_date->diffInDays($asOf, false));
            $bucket = match (true) {
                $daysPastDue === 0 => 'current',
                $daysPastDue <= 30 => '1_30',
                $daysPastDue <= 60 => '31_60',
                $daysPastDue <= 90 => '61_90',
                default => 'over_90',
            };
            if ($bill->type === VendorBillType::Invoice) {
                $buckets[$bucket] = bcadd($buckets[$bucket], $bill->postedOpenAmount($asOf), 4);
            }
        }

        return ['as_of' => $asOf->toDateString(), 'buckets' => $buckets, 'bills' => $bills];
    }
}
