<?php

namespace App\Filament\Resources\AttendanceRules\Pages;

use App\Filament\Resources\AttendanceRules\AttendanceRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceRules extends ListRecords
{
    protected static string $resource = AttendanceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
