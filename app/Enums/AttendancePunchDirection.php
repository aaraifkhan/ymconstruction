<?php

namespace App\Enums;

enum AttendancePunchDirection: string
{
    case In = 'in';
    case Out = 'out';
    case BreakOut = 'break_out';
    case BreakIn = 'break_in';
}
