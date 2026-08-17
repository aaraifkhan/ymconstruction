<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpensePaymentMethod: string implements HasLabel
{
    case Cash = 'cash';
    case Bank = 'bank';
    case PettyCash = 'petty_cash';
    case Director = 'director';
    case StaffPayable = 'staff_payable';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Head Office Cash',
            self::Bank => 'Company Bank Account',
            self::PettyCash => 'Petty Cash Float',
            self::Director => 'Director Funded (Due to Director)',
            self::StaffPayable => 'Staff Reimbursement Payable',
        };
    }
}
