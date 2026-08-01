<?php

namespace App\Enums;

enum PayrollAccountComponent: string
{
    case BasicSalary = 'basic_salary';
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
    case OtherDeduction = 'other_deduction';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
