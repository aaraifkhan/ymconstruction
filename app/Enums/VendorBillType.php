<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VendorBillType: string implements HasLabel
{
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
