<?php

namespace App\Policies;

class PerformanceKpiPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PerformanceKpi';
}
