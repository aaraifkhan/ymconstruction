<?php

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceRecord extends CreateRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'state' => 'draft',
            'shift_assignment_id' => null,
            'attendance_rule_id' => null,
            'first_in_at' => null,
            'last_out_at' => null,
            'scheduled_minutes' => 0,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'source_checksum' => null,
            'finalized_by_id' => null,
            'finalized_at' => null,
        ];
    }
}
