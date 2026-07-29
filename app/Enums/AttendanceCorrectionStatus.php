<?php

namespace App\Enums;

enum AttendanceCorrectionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
