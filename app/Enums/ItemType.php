<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ItemType: string implements HasLabel
{
    case Material = 'material';
    case Service = 'service';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
