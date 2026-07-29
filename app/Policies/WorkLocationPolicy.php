<?php

namespace App\Policies;

class WorkLocationPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'WorkLocation';
}
