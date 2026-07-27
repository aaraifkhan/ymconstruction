<?php

namespace App\Filament\Resources\AccountingMappings\Pages;

use App\Filament\Resources\AccountingMappings\AccountingMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountingMapping extends ViewRecord
{
    protected static string $resource = AccountingMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
