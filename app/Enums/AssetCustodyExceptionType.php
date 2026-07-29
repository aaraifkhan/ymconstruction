<?php

namespace App\Enums;

enum AssetCustodyExceptionType: string
{
    case Damage = 'damage';
    case Loss = 'loss';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
