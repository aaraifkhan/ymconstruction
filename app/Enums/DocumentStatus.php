<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Verified => 'Verified',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Verified => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
