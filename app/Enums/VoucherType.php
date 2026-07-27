<?php

namespace App\Enums;

enum VoucherType: string
{
    case Journal = 'journal';
    case Payment = 'payment';
    case Receipt = 'receipt';
    case Contra = 'contra';
    case Purchase = 'purchase';
    case Sales = 'sales';
    case DebitNote = 'debit_note';
    case CreditNote = 'credit_note';
    case OpeningBalance = 'opening_balance';
    case Payroll = 'payroll';
    case Depreciation = 'depreciation';
    case InventoryAdjustment = 'inventory_adjustment';
    case Reversal = 'reversal';
    case InterCompany = 'inter_company';

    public function prefix(): string
    {
        return match ($this) {
            self::Journal => 'JV',
            self::Payment => 'PV',
            self::Receipt => 'RV',
            self::Contra => 'CV',
            self::Purchase => 'PUR',
            self::Sales => 'SAL',
            self::DebitNote => 'DN',
            self::CreditNote => 'CN',
            self::OpeningBalance => 'OB',
            self::Payroll => 'PAY',
            self::Depreciation => 'DEP',
            self::InventoryAdjustment => 'IA',
            self::Reversal => 'REV',
            self::InterCompany => 'IC',
        };
    }
}
