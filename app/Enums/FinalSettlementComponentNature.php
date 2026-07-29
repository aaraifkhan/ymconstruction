<?php

namespace App\Enums;

enum FinalSettlementComponentNature: string
{
    case Earning = 'earning';
    case Recovery = 'recovery';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
