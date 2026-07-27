<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InspectionResult: string implements HasLabel
{
    case Accepted = 'accepted';
    case PartiallyAccepted = 'partially_accepted';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
