<?php

namespace App\Filament\Resources\EmployeeClearances\Pages;

use App\Filament\Resources\EmployeeClearances\EmployeeClearanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeClearances extends ListRecords
{
    protected static string $resource = EmployeeClearanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
