<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Pages;

use App\Filament\Resources\EmployeeCodeSequences\EmployeeCodeSequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeCodeSequences extends ListRecords
{
    protected static string $resource = EmployeeCodeSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
