<?php

namespace App\Policies;

class CustomerInvoiceAdjustmentPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'CustomerInvoiceAdjustment';
}
