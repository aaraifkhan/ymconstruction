<?php

namespace App\Enums;

enum AppraisalCycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
}
