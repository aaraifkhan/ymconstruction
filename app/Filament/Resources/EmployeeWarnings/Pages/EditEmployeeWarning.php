<?php

namespace App\Filament\Resources\EmployeeWarnings\Pages;

use App\Filament\Resources\EmployeeWarnings\EmployeeWarningResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeWarning extends EditRecord
{
    protected static string $resource = EmployeeWarningResource::class;

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
