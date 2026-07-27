<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProcurementApprovalStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
