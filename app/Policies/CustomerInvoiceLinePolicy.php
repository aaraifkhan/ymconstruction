<?php

namespace App\Policies;

class CustomerInvoiceLinePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'CustomerInvoiceLine';
}
