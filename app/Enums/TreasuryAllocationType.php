<?php

namespace App\Enums;

enum TreasuryAllocationType: string
{
    case VendorBill = 'vendor_bill';
    case CustomerInvoice = 'customer_invoice';
    case PayrollEntry = 'payroll_entry';
}
