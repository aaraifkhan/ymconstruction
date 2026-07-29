<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Pages;

use App\Filament\Resources\AttendanceMonthlySummaries\AttendanceMonthlySummaryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceMonthlySummary extends ViewRecord
{
    protected static string $resource = AttendanceMonthlySummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
