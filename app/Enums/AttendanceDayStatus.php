<?php

namespace App\Enums;

enum AttendanceDayStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case HalfDay = 'half_day';
    case PaidLeave = 'paid_leave';
    case UnpaidLeave = 'unpaid_leave';
    case Holiday = 'holiday';
    case RestDay = 'rest_day';
    case MissingPunch = 'missing_punch';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
