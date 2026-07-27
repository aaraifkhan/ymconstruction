<?php

namespace App\Enums;

enum YearEndClosingStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
