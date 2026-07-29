<?php

namespace App\Filament\Resources\AttendancePunches\Pages;

use App\Filament\Resources\AttendancePunches\AttendancePunchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendancePunch extends CreateRecord
{
    protected static string $resource = AttendancePunchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();
        $data['status'] = 'pending';
        $data['approved_by_id'] = null;
        $data['approved_at'] = null;
        $data['rejection_reason'] = null;

        return $data;
    }
}
