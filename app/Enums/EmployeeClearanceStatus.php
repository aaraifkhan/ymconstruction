<?php

namespace App\Enums;

enum EmployeeClearanceStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
