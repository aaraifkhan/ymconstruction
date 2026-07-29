<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Pages;

use App\Filament\Resources\AttendanceDeviceUserMappings\AttendanceDeviceUserMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceDeviceUserMappings extends ListRecords
{
    protected static string $resource = AttendanceDeviceUserMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
