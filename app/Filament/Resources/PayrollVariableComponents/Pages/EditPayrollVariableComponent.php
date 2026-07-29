<?php

namespace App\Filament\Resources\PayrollVariableComponents\Pages;

use App\Filament\Resources\PayrollVariableComponents\PayrollVariableComponentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollVariableComponent extends EditRecord
{
    protected static string $resource = PayrollVariableComponentResource::class;

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
