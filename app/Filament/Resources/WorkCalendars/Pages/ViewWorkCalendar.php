<?php

namespace App\Filament\Resources\WorkCalendars\Pages;

use App\Filament\Resources\WorkCalendars\WorkCalendarResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkCalendar extends ViewRecord
{
    protected static string $resource = WorkCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
