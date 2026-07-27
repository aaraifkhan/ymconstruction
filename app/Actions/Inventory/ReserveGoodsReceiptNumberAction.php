<?php

namespace App\Actions\Inventory;

use App\Actions\Procurement\ReserveProcurementNumberAction;
use App\Enums\ProcurementDocumentType;
use App\Models\Company;

class ReserveGoodsReceiptNumberAction
{
    public function __construct(private ReserveProcurementNumberAction $reserveNumber) {}

    public function handle(Company $company, int $calendarYear): string
    {
        return $this->reserveNumber->handle(
            $company,
            ProcurementDocumentType::GoodsReceipt,
            $calendarYear,
        );
    }
}
