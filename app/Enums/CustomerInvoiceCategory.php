<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomerInvoiceCategory: string implements HasLabel
{
    case RunningBill = 'running_bill';
    case ServiceInvoice = 'service_invoice';
    case TradingSale = 'trading_sale';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
