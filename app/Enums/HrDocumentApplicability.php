<?php

namespace App\Enums;

enum HrDocumentApplicability: string
{
    case Employee = 'employee';
    case Employment = 'employment';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee profile',
            self::Employment => 'Company employment',
        };
    }
}
