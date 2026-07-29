<?php

namespace App\Enums;

enum EmployeeClearanceItemStatus: string
{
    case Pending = 'pending';
    case Cleared = 'cleared';
    case Blocked = 'blocked';
    case RecoveryRecommended = 'recovery_recommended';
    case Waived = 'waived';

    public function resolvesChecklist(): bool
    {
        return in_array($this, [self::Cleared, self::RecoveryRecommended, self::Waived], true);
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
