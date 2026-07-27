<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InventoryTransactionStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Posted = 'posted';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
