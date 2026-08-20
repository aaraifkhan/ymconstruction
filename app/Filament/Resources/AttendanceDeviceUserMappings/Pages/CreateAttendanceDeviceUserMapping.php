<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Pages;

use App\Filament\Resources\AttendanceDeviceUserMappings\AttendanceDeviceUserMappingResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceDeviceUserMapping extends CreateRecord
{
    protected static string $resource = AttendanceDeviceUserMappingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->getKey();

        return $data;
    }
}
