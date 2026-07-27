<?php

namespace App\Filament\Resources\AccountingSettings\Pages;

use App\Filament\Resources\AccountingSettings\AccountingSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountingSetting extends ViewRecord
{
    protected static string $resource = AccountingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
