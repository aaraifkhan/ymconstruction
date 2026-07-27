<?php

namespace App\Policies;

class AssetCategoryPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AssetCategory';
}
