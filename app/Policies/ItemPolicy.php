<?php

namespace App\Policies;

class ItemPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'Item';
}
