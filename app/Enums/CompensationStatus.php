<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompensationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
