<?php

namespace App\Reports;

use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Models\Company;
use App\Models\CustomerInvoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AccountsReceivableAgingReport
{
    /** @return array{as_of:string, buckets:array<string, string>, invoices:Collection<int, CustomerInvoice>} */
    public function forCompany(Company $company, CarbonInterface $asOf): array
    {
        $invoices = CustomerInvoice::query()
            ->whereBelongsTo($company)
            ->where('type', CustomerInvoiceType::Invoice->value)
            ->where('status', CustomerInvoiceStatus::Posted->value)
            ->whereDate('invoice_date', '<=', $asOf)
            ->with('customer:id,name')
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current' => '0.0000',
            '1_30' => '0.0000',
            '31_60' => '0.0000',
            '61_90' => '0.0000',
            'over_90' => '0.0000',
        ];

        foreach ($invoices as $invoice) {
            $openAmount = $invoice->postedOpenAmount($asOf);
            if (bccomp($openAmount, '0', 4) !== 1) {
                continue;
            }
            $daysPastDue = (int) max(0, $invoice->due_date->diffInDays($asOf, false));
            $bucket = match (true) {
                $daysPastDue === 0 => 'current',
                $daysPastDue <= 30 => '1_30',
                $daysPastDue <= 60 => '31_60',
                $daysPastDue <= 90 => '61_90',
                default => 'over_90',
            };
            $buckets[$bucket] = bcadd($buckets[$bucket], $openAmount, 4);
        }

        return ['as_of' => $asOf->toDateString(), 'buckets' => $buckets, 'invoices' => $invoices];
    }
}
