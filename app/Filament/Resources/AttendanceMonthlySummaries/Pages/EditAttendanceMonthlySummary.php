<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Pages;

use App\Filament\Resources\AttendanceMonthlySummaries\AttendanceMonthlySummaryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceMonthlySummary extends EditRecord
{
    protected static string $resource = AttendanceMonthlySummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
