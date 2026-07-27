<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaxCalculationMethod: string implements HasLabel
{
    case Exclusive = 'exclusive';
    case Inclusive = 'inclusive';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
