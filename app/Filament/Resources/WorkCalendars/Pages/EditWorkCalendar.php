<?php

namespace App\Filament\Resources\WorkCalendars\Pages;

use App\Filament\Resources\WorkCalendars\WorkCalendarResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkCalendar extends EditRecord
{
    protected static string $resource = WorkCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
