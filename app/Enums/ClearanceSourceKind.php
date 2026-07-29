<?php

namespace App\Enums;

enum ClearanceSourceKind: string
{
    case Asset = 'asset';
    case Loan = 'loan';
    case Advance = 'advance';
    case Leave = 'leave';
    case Handover = 'handover';
    case Configured = 'configured';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
