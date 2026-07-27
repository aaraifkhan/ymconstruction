<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectBudgetStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Superseded = 'superseded';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Approved => 'success',
            self::Superseded => 'warning',
        };
    }
}
