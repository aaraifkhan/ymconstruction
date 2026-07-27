<?php

namespace App\Enums;

enum BankStatementStatus: string
{
    case Draft = 'draft';
    case Imported = 'imported';
    case Locked = 'locked';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
