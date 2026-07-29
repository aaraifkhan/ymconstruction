<?php

namespace App\Filament\Resources\AttendanceImportBatches\Pages;

use App\Filament\Resources\AttendanceImportBatches\AttendanceImportBatchResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceImportBatch extends ViewRecord
{
    protected static string $resource = AttendanceImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
