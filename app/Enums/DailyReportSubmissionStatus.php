<?php

namespace App\Enums;

enum DailyReportSubmissionStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';
    case Missing = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::OnTime => 'On Time (<= 6:00 PM)',
            self::Late => 'Late Submission (> 6:00 PM)',
            self::Missing => 'Missing / Not Submitted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OnTime => 'success',
            self::Late => 'warning',
            self::Missing => 'danger',
        };
    }
}
