<?php

namespace App\Filament\Resources\AttendanceImportRowErrors\Pages;

use App\Filament\Resources\AttendanceImportRowErrors\AttendanceImportRowErrorResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceImportRowError extends EditRecord
{
    protected static string $resource = AttendanceImportRowErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
