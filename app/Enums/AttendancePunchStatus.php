<?php

namespace App\Enums;

enum AttendancePunchStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
