<?php

namespace App\Enums;

enum AttendanceSummaryStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
}
