<?php

namespace App\Policies;

class PartyContactPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'PartyContact';
}
