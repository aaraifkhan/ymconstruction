<?php

namespace App\Filament\Resources\ShiftAssignments\Pages;

use App\Filament\Resources\ShiftAssignments\ShiftAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewShiftAssignment extends ViewRecord
{
    protected static string $resource = ShiftAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
