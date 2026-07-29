<?php

namespace App\Filament\Resources\AttendanceImportRowErrors\Pages;

use App\Filament\Resources\AttendanceImportRowErrors\AttendanceImportRowErrorResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceImportRowError extends ViewRecord
{
    protected static string $resource = AttendanceImportRowErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
