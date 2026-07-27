<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomerInvoiceAdjustmentType: string implements HasLabel
{
    case Retention = 'retention';
    case WithholdingTax = 'withholding_tax';
    case MobilizationRecovery = 'mobilization_recovery';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
