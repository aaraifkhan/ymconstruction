<?php

namespace App\Enums;

enum TreasuryStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
