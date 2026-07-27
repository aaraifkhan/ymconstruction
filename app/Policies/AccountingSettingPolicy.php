<?php

namespace App\Policies;

class AccountingSettingPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AccountingSetting';
}
