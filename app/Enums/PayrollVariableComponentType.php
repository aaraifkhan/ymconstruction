<?php

namespace App\Enums;

enum PayrollVariableComponentType: string
{
    case Bonus = 'bonus';
    case Incentive = 'incentive';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
