<?php

namespace App\Enums;

enum OpeningBalanceMigrationStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Imported = 'imported';
    case Failed = 'failed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
