<?php

namespace App\Enums;

enum JournalStatus: string
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
