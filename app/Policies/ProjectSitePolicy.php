<?php

namespace App\Policies;

class ProjectSitePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'ProjectSite';
}
