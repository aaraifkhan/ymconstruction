<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VendorBillDeductionType: string implements HasLabel
{
    case WithholdingTax = 'withholding_tax';
    case Retention = 'retention';
    case VendorAdvance = 'vendor_advance';
    case Other = 'other';

    public function mappingKey(): ?AccountingMappingKey
    {
        return match ($this) {
            self::WithholdingTax => AccountingMappingKey::WhtPayable,
            self::Retention => AccountingMappingKey::RetentionPayable,
            self::VendorAdvance => AccountingMappingKey::VendorAdvances,
            self::Other => null,
        };
    }

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
