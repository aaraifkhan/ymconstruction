<?php

namespace App\Enums;

enum IntercompanyStatus: string
{
    case Draft = 'draft';
    case PendingApprovals = 'pending_approvals';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
