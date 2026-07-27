<?php

namespace App\Enums;

enum FinancialPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
