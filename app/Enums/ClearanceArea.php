<?php

namespace App\Enums;

enum ClearanceArea: string
{
    case Hr = 'hr';
    case Manager = 'manager';
    case It = 'it';
    case Administration = 'administration';
    case Store = 'store';
    case Finance = 'finance';
    case Loans = 'loans';
    case Assets = 'assets';

    public function permissionAbility(): string
    {
        return 'Clear'.str($this->value)->headline()->replace(' ', '').':EmployeeClearance';
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
