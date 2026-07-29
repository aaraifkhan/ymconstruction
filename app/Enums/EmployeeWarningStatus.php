<?php

namespace App\Enums;

enum EmployeeWarningStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Responded = 'responded';
    case Acknowledged = 'acknowledged';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
