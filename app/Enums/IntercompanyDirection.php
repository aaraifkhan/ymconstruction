<?php

namespace App\Enums;

enum IntercompanyDirection: string
{
    case OriginReceivable = 'origin_receivable';
    case OriginPayable = 'origin_payable';

    public function label(): string
    {
        return match ($this) {
            self::OriginReceivable => 'Origin company is receivable',
            self::OriginPayable => 'Origin company is payable',
        };
    }
}
