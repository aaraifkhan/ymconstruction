<?php

namespace App\Filament\Resources\AttendanceImportRowErrors\Pages;

use App\Filament\Resources\AttendanceImportRowErrors\AttendanceImportRowErrorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceImportRowErrors extends ListRecords
{
    protected static string $resource = AttendanceImportRowErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
