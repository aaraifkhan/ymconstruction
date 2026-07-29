<?php

namespace App\Filament\Resources\AttendanceRawEvents\Pages;

use App\Filament\Resources\AttendanceRawEvents\AttendanceRawEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceRawEvent extends EditRecord
{
    protected static string $resource = AttendanceRawEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
