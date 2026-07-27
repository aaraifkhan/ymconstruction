<?php

namespace App\Enums;

enum AssetAcquisitionSource: string
{
    case Manual = 'manual';
    case VendorBill = 'vendor_bill';
}
