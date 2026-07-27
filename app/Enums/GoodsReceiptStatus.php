<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GoodsReceiptStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Received = 'received';
    case Inspected = 'inspected';
    case HandedOver = 'handed_over';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
