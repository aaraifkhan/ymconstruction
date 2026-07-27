<?php

namespace App\Actions\AccountsPayable;

use App\Actions\Procurement\ReserveProcurementNumberAction;
use App\Enums\ProcurementDocumentType;
use App\Enums\VendorBillType;
use App\Models\VendorBill;

class ReserveVendorBillNumberAction
{
    public function __construct(private ReserveProcurementNumberAction $reserveNumber) {}

    public function handle(VendorBill $bill): string
    {
        return $this->reserveNumber->handle(
            $bill->company()->firstOrFail(),
            $bill->type === VendorBillType::Invoice
                ? ProcurementDocumentType::VendorBill
                : ProcurementDocumentType::VendorCreditNote,
            $bill->invoice_date->year,
        );
    }
}
