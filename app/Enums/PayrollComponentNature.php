<?php

namespace App\Enums;

enum PayrollComponentNature: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';
}
