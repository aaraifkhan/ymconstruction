<?php

namespace App\Filament\Resources\AttendanceRawEvents\Pages;

use App\Filament\Resources\AttendanceRawEvents\AttendanceRawEventResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceRawEvent extends ViewRecord
{
    protected static string $resource = AttendanceRawEventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
