<?php

namespace App\Enums;

enum EmploymentMovementStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
