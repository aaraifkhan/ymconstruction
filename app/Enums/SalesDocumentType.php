<?php

namespace App\Enums;

enum SalesDocumentType: string
{
    case RunningBill = 'running_bill';
    case ServiceInvoice = 'service_invoice';
    case TradingSale = 'trading_sale';
    case CustomerCreditNote = 'customer_credit_note';

    public function prefix(): string
    {
        return match ($this) {
            self::RunningBill => 'RB',
            self::ServiceInvoice => 'SI',
            self::TradingSale => 'TS',
            self::CustomerCreditNote => 'SCN',
        };
    }
}
