<?php

namespace App\Filament\Resources\AttendanceRules\Pages;

use App\Filament\Resources\AttendanceRules\AttendanceRuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceRule extends ViewRecord
{
    protected static string $resource = AttendanceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
