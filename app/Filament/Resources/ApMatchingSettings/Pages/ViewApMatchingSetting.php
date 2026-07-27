<?php

namespace App\Filament\Resources\ApMatchingSettings\Pages;

use App\Filament\Resources\ApMatchingSettings\ApMatchingSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApMatchingSetting extends ViewRecord
{
    protected static string $resource = ApMatchingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
