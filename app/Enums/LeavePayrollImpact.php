<?php

namespace App\Enums;

enum LeavePayrollImpact: string
{
    case None = 'none';
    case UnpaidDeduction = 'unpaid_deduction';
}
