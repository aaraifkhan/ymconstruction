<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PayrollRunStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Paid = 'paid';
    case Locked = 'locked';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Paid => 'info',
            self::Locked => 'primary',
            self::Rejected => 'danger',
        };
    }
}
