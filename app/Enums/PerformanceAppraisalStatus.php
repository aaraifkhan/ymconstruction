<?php

namespace App\Enums;

enum PerformanceAppraisalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Acknowledged = 'acknowledged';
    case Rejected = 'rejected';
}
