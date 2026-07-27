<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaxCodeType: string implements HasLabel
{
    case SalesTax = 'sales_tax';
    case WithholdingTax = 'withholding_tax';
    case Other = 'other';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
