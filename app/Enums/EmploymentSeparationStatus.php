<?php

namespace App\Enums;

enum EmploymentSeparationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Approved = 'approved';
    case Withdrawn = 'withdrawn';
    case Rejected = 'rejected';
}
