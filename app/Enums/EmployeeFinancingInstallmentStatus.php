<?php

namespace App\Enums;

enum EmployeeFinancingInstallmentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';
    case Superseded = 'superseded';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
