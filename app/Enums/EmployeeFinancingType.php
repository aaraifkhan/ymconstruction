<?php

namespace App\Enums;

enum EmployeeFinancingType: string
{
    case Loan = 'loan';
    case Advance = 'advance';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
