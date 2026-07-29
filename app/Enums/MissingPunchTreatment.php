<?php

namespace App\Enums;

enum MissingPunchTreatment: string
{
    case Flag = 'flag';
    case Absent = 'absent';
    case HalfDay = 'half_day';
}
