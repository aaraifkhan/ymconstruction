<?php

namespace App\Filament\Resources\AccountingSettings\Pages;

use App\Filament\Resources\AccountingSettings\AccountingSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountingSettings extends ListRecords
{
    protected static string $resource = AccountingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
