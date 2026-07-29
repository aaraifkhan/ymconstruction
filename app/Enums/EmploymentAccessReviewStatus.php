<?php

namespace App\Enums;

enum EmploymentAccessReviewStatus: string
{
    case NotApplicable = 'not_applicable';
    case Pending = 'pending';
    case Completed = 'completed';
}
