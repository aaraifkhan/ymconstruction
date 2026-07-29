<?php

namespace App\Enums;

enum EmployeeFinancingTransactionType: string
{
    case Disbursement = 'disbursement';
    case TreasuryRecovery = 'treasury_recovery';
    case PayrollRecovery = 'payroll_recovery';
    case FinalSettlementRecovery = 'final_settlement_recovery';
    case Waiver = 'waiver';
    case Reversal = 'reversal';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
