<?php

namespace App\Policies;

class PerformanceAppraisalItemPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PerformanceAppraisalItem';
}
