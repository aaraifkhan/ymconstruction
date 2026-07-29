<?php

namespace App\Contracts;

use App\Data\AttendanceDevicePullResult;
use App\Models\AttendanceDevice;

interface AttendanceDeviceAdapter
{
    public function pull(AttendanceDevice $device, ?string $cursor): AttendanceDevicePullResult;
}
