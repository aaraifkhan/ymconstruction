<?php

namespace App\Enums;

enum BankReconciliationStatus: string
{
    case Draft = 'draft';
    case Closed = 'closed';
    case Reopened = 'reopened';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
