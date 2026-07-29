<?php

namespace App\Enums;

enum EmployeeFinancingStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Approved = 'approved';
    case DisbursementPending = 'disbursement_pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Settled = 'settled';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
