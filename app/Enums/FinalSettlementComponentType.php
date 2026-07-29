<?php

namespace App\Enums;

enum FinalSettlementComponentType: string
{
    case Salary = 'salary';
    case LeaveEncashment = 'leave_encashment';
    case NoticePay = 'notice_pay';
    case Bonus = 'bonus';
    case Incentive = 'incentive';
    case Gratuity = 'gratuity';
    case OtherBenefit = 'other_benefit';
    case LoanRecovery = 'loan_recovery';
    case AdvanceRecovery = 'advance_recovery';
    case AssetRecovery = 'asset_recovery';
    case NoticeRecovery = 'notice_recovery';
    case OtherRecovery = 'other_recovery';

    public function nature(): FinalSettlementComponentNature
    {
        return match ($this) {
            self::Salary, self::LeaveEncashment, self::NoticePay, self::Bonus,
            self::Incentive, self::Gratuity, self::OtherBenefit => FinalSettlementComponentNature::Earning,
            default => FinalSettlementComponentNature::Recovery,
        };
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function usesEmployeeAdvancesMapping(): bool
    {
        return in_array($this, [self::LoanRecovery, self::AdvanceRecovery], true);
    }
}
