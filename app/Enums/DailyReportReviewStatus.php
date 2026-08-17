<?php

namespace App\Enums;

enum DailyReportReviewStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Acknowledged = 'acknowledged';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Reviewed => 'Reviewed by Lead',
            self::Acknowledged => 'Acknowledged by Head',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reviewed => 'info',
            self::Acknowledged => 'success',
        };
    }
}
