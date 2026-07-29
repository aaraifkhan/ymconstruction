<?php

namespace App\Enums;

enum AttendanceDeviceHealthStatus: string
{
    case Unknown = 'unknown';
    case Online = 'online';
    case Offline = 'offline';
    case Error = 'error';
}
