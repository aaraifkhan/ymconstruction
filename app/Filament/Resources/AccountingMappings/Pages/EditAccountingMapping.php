<?php

namespace App\Filament\Resources\AccountingMappings\Pages;

use App\Filament\Resources\AccountingMappings\AccountingMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountingMapping extends EditRecord
{
    protected static string $resource = AccountingMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
