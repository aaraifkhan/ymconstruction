<?php

namespace App\Enums;

enum TreasuryTransactionType: string
{
    case Payment = 'payment';
    case Receipt = 'receipt';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment',
            self::Receipt => 'Receipt',
            self::Transfer => 'Cash / Bank Transfer',
        };
    }
}
