<?php

namespace App\Filament\Resources\AttendanceImportBatches\Pages;

use App\Filament\Resources\AttendanceImportBatches\AttendanceImportBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceImportBatch extends EditRecord
{
    protected static string $resource = AttendanceImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
