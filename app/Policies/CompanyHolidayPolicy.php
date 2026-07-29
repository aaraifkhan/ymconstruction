<?php

namespace App\Policies;

class CompanyHolidayPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'CompanyHoliday';
}
