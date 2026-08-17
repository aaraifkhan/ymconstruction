<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VoucherType: string implements HasLabel
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

    public function getLabel(): string
    {
        return match ($this) {
            self::Journal => 'Journal Voucher',
            self::Payment => 'Payment Voucher',
            self::Receipt => 'Receipt Voucher',
            self::Contra => 'Contra Voucher',
            self::Purchase => 'Purchase Voucher',
            self::Sales => 'Sales Invoice Voucher',
            self::DebitNote => 'Debit Note',
            self::CreditNote => 'Credit Note',
            self::OpeningBalance => 'Opening Balance Voucher',
            self::Payroll => 'Payroll Voucher',
            self::Depreciation => 'Depreciation Voucher',
            self::InventoryAdjustment => 'Inventory Adjustment',
            self::Reversal => 'Reversal Voucher',
            self::InterCompany => 'Inter-Company Voucher',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
