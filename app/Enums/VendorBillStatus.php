<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VendorBillStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
