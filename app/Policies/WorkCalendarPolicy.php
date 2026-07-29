<?php

namespace App\Policies;

class WorkCalendarPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'WorkCalendar';
}
