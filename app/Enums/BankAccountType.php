<?php

namespace App\Enums;

enum BankAccountType: string
{
    case Current = 'current';
    case Savings = 'savings';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Current account',
            self::Savings => 'Savings account',
            self::Other => 'Other',
        };
    }
}
