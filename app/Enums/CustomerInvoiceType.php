<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomerInvoiceType: string implements HasLabel
{
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
