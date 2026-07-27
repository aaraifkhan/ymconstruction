<?php

namespace App\Enums;

enum JoiningLetterStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Issued = 'issued';
    case Accepted = 'accepted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Issued => 'Issued',
            self::Accepted => 'Accepted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Issued => 'info',
            self::Accepted => 'success',
        };
    }
}
