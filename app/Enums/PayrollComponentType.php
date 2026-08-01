<?php

namespace App\Enums;

enum PayrollComponentType: string
{
    case PayableBasic = 'payable_basic';
    case HouseTravelAllowance = 'house_travel_allowance';
    case FuelAllowance = 'fuel_allowance';
    case MobileAllowance = 'mobile_allowance';
    case InternetAllowance = 'internet_allowance';
    case FoodAllowance = 'food_allowance';
    case SiteAllowance = 'site_allowance';
    case ProjectAllowance = 'project_allowance';
    case OtherAllowance = 'other_allowance';
    case Bonus = 'bonus';
    case Incentive = 'incentive';
    case AbsenceDeduction = 'absence_deduction';
    case UnpaidLeaveDeduction = 'unpaid_leave_deduction';
    case LateDeduction = 'late_deduction';
    case HalfDayDeduction = 'half_day_deduction';
    case LoanInstallment = 'loan_installment';
    case AdvanceRecovery = 'advance_recovery';
    case OtherDeduction = 'other_deduction';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
