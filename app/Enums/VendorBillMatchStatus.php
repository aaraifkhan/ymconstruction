<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VendorBillMatchStatus: string implements HasLabel
{
    case Matched = 'matched';
    case WithinTolerance = 'within_tolerance';
    case ExceptionApproved = 'exception_approved';
    case NotApplicable = 'not_applicable';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
