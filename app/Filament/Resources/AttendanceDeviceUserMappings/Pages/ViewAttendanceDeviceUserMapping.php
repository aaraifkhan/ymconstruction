<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Pages;

use App\Filament\Resources\AttendanceDeviceUserMappings\AttendanceDeviceUserMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceDeviceUserMapping extends ViewRecord
{
    protected static string $resource = AttendanceDeviceUserMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
