<?php

namespace App\Enums;

enum AttendanceImportSource: string
{
    case Csv = 'csv';
    case Manual = 'manual';
    case DeviceAdapter = 'device_adapter';
}
