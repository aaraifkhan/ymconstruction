<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PartyRole: string implements HasLabel
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Contractor = 'contractor';
    case Consultant = 'consultant';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
