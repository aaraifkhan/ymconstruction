<?php

namespace App\Enums;

enum PayrollAccountComponent: string
{
    case BasicSalary = 'basic_salary';
    case HouseTravelAllowance = 'house_travel_allowance';
    case FoodAllowance = 'food_allowance';
    case OtherAllowance = 'other_allowance';
    case AbsenceDeduction = 'absence_deduction';
    case OtherDeduction = 'other_deduction';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
