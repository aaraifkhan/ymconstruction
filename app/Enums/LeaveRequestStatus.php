<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case ManagerApproved = 'manager_approved';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
