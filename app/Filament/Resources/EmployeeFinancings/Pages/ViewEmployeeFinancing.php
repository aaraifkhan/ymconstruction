<?php

namespace App\Filament\Resources\EmployeeFinancings\Pages;

use App\Filament\Resources\EmployeeFinancings\EmployeeFinancingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeFinancing extends ViewRecord
{
    protected static string $resource = EmployeeFinancingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
