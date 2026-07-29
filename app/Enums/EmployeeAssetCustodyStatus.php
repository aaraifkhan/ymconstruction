<?php

namespace App\Enums;

enum EmployeeAssetCustodyStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Acknowledged = 'acknowledged';
    case ReturnPending = 'return_pending';
    case Returned = 'returned';
    case Transferred = 'transferred';
    case Exception = 'exception';

    public function isOpen(): bool
    {
        return in_array($this, [self::Issued, self::Acknowledged, self::ReturnPending, self::Exception], true);
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
