<?php

namespace App\Reports;

use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Models\Company;
use App\Models\CustomerInvoice;
use Illuminate\Support\Collection;

class UnpaidCustomerInvoiceReport
{
    /** @return Collection<int, array{invoice:CustomerInvoice, credit_notes:string, receipts:string, open_amount:string}> */
    public function forCompany(Company $company): Collection
    {
        return CustomerInvoice::query()
            ->whereBelongsTo($company)
            ->where('type', CustomerInvoiceType::Invoice->value)
            ->where('status', CustomerInvoiceStatus::Posted->value)
            ->with('customer:id,name')
            ->orderBy('due_date')
            ->get()
            ->map(function (CustomerInvoice $invoice): array {
                $credits = $invoice->creditedAmount();
                $receipts = $invoice->settledAmount();

                return [
                    'invoice' => $invoice,
                    'credit_notes' => $credits,
                    'receipts' => $receipts,
                    'open_amount' => $invoice->openAmount(),
                ];
            })
            ->filter(fn (array $row): bool => bccomp($row['open_amount'], '0', 4) === 1)
            ->values();
    }
}
