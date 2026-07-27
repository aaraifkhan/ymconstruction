<?php

namespace App\Enums;

enum CompanyModuleState: string
{
    case Inherit = 'inherit';
    case Enabled = 'enabled';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Inherit => 'Inherit from parent company',
            self::Enabled => 'Enabled',
            self::Disabled => 'Disabled',
        };
    }
}
