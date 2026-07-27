<?php

namespace App\Enums;

enum TreasuryPurpose: string
{
    case Settlement = 'settlement';
    case Advance = 'advance';
    case Refund = 'refund';
    case Other = 'other';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
