<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case DailyWages = 'daily_wages';
    case Internship = 'internship';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Permanent',
            self::Contract => 'Contract',
            self::DailyWages => 'Daily Wages',
            self::Internship => 'Internship',
        };
    }
}
