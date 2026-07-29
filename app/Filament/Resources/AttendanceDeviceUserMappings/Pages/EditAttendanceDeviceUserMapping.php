<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Pages;

use App\Filament\Resources\AttendanceDeviceUserMappings\AttendanceDeviceUserMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceDeviceUserMapping extends EditRecord
{
    protected static string $resource = AttendanceDeviceUserMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
