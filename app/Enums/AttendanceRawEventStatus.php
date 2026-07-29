<?php

namespace App\Enums;

enum AttendanceRawEventStatus: string
{
    case Pending = 'pending';
    case Quarantined = 'quarantined';
    case Processed = 'processed';
    case RequiresReview = 'requires_review';
    case Failed = 'failed';
}
