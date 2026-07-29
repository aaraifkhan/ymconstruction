<?php

namespace App\Enums;

enum AttendancePunchSource: string
{
    case Manual = 'manual';
    case Machine = 'machine';
}
