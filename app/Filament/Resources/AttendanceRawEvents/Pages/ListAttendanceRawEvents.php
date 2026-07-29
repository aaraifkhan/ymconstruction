<?php

namespace App\Filament\Resources\AttendanceRawEvents\Pages;

use App\Filament\Resources\AttendanceRawEvents\AttendanceRawEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceRawEvents extends ListRecords
{
    protected static string $resource = AttendanceRawEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
