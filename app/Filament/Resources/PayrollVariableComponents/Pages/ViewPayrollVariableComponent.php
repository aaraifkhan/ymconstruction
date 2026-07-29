<?php

namespace App\Filament\Resources\PayrollVariableComponents\Pages;

use App\Filament\Resources\PayrollVariableComponents\PayrollVariableComponentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayrollVariableComponent extends ViewRecord
{
    protected static string $resource = PayrollVariableComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
