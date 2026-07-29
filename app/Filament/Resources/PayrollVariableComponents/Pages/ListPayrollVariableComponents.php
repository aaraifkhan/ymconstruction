<?php

namespace App\Filament\Resources\PayrollVariableComponents\Pages;

use App\Filament\Resources\PayrollVariableComponents\PayrollVariableComponentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollVariableComponents extends ListRecords
{
    protected static string $resource = PayrollVariableComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
