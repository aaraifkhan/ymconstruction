<?php

namespace App\Enums;

enum LeaveLedgerEntryType: string
{
    case Opening = 'opening';
    case Accrual = 'accrual';
    case Adjustment = 'adjustment';
    case Consumption = 'consumption';
    case Reversal = 'reversal';
}
