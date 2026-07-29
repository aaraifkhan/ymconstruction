<?php

namespace App\Filament\Resources\AttendanceCorrections\Pages;

use App\Filament\Resources\AttendanceCorrections\AttendanceCorrectionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceCorrection extends EditRecord
{
    protected static string $resource = AttendanceCorrectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
