<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Probation = 'probation';
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Terminated = 'terminated';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Probation => 'Probation',
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
            self::Ended => 'Ended',
        };
    }

    public function isLegacy(): bool
    {
        return $this === self::Ended;
    }
}
