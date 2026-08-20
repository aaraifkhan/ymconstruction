<?php

namespace App\Filament\Resources\AttendanceDevices\Pages;

use App\Filament\Resources\AttendanceDevices\AttendanceDeviceResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceDevice extends CreateRecord
{
    protected static string $resource = AttendanceDeviceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->getKey();

        return $data;
    }
}
