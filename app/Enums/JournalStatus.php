<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JournalStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'warning',
            self::Approved => 'info',
            self::Posted => 'success',
            self::Rejected => 'danger',
            self::Reversed => 'gray',
        };
    }
}
