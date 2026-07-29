<?php

namespace App\Enums;

enum FinalSettlementStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Posted = 'posted';
    case Settled = 'settled';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
