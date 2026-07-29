<?php

namespace App\Enums;

enum AttendanceRecordState: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
}
