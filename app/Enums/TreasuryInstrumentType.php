<?php

namespace App\Enums;

enum TreasuryInstrumentType: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case PayOrder = 'pay_order';
    case BankDraft = 'bank_draft';
    case Electronic = 'electronic';
    case Other = 'other';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
