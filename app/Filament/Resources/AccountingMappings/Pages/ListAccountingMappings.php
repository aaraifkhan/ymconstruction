<?php

namespace App\Filament\Resources\AccountingMappings\Pages;

use App\Filament\Resources\AccountingMappings\AccountingMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountingMappings extends ListRecords
{
    protected static string $resource = AccountingMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
