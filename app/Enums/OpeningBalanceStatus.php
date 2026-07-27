<?php

namespace App\Enums;

enum OpeningBalanceStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Posted = 'posted';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
