<?php

namespace App\Filament\Resources\EmployeeFinancings\Pages;

use App\Filament\Resources\EmployeeFinancings\EmployeeFinancingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeFinancings extends ListRecords
{
    protected static string $resource = EmployeeFinancingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
