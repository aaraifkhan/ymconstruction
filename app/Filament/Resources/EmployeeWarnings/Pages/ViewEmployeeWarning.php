<?php

namespace App\Filament\Resources\EmployeeWarnings\Pages;

use App\Filament\Resources\EmployeeWarnings\EmployeeWarningResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeWarning extends ViewRecord
{
    protected static string $resource = EmployeeWarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
