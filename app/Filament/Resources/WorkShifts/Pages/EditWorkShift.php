<?php

namespace App\Filament\Resources\WorkShifts\Pages;

use App\Filament\Resources\WorkShifts\WorkShiftResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkShift extends EditRecord
{
    protected static string $resource = WorkShiftResource::class;

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
