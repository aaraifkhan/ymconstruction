<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasColor, HasLabel
{
    case Planned = 'planned';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Active => 'success',
            self::OnHold => 'warning',
            self::Completed => 'info',
            self::Cancelled => 'danger',
        };
    }
}
