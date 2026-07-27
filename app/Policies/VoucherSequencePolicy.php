<?php

namespace App\Policies;

class VoucherSequencePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'VoucherSequence';
}
