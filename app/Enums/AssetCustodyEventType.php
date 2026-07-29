<?php

namespace App\Enums;

enum AssetCustodyEventType: string
{
    case Issued = 'issued';
    case Acknowledged = 'acknowledged';
    case Transferred = 'transferred';
    case ReturnRequested = 'return_requested';
    case Returned = 'returned';
    case DamageReported = 'damage_reported';
    case LossReported = 'loss_reported';
    case ExceptionResolved = 'exception_resolved';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
