<?php

namespace App\Filament\Resources\AttendanceCorrections\Pages;

use App\Filament\Resources\AttendanceCorrections\AttendanceCorrectionResource;
use App\Models\AttendanceRecord;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceCorrection extends CreateRecord
{
    protected static string $resource = AttendanceCorrectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $record = AttendanceRecord::query()->findOrFail($data['attendance_record_id']);
        $data['before_snapshot'] = $record->only([
            'day_status', 'first_in_at', 'last_out_at', 'scheduled_minutes',
            'worked_minutes', 'late_minutes', 'overtime_minutes', 'notes',
        ]);
        $data['requested_by_id'] = auth()->id();
        $data['status'] = 'pending';
        $data['decided_by_id'] = null;
        $data['decided_at'] = null;
        $data['decision_reason'] = null;

        return $data;
    }
}
