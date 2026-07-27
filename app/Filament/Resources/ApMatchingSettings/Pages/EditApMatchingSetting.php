<?php

namespace App\Filament\Resources\ApMatchingSettings\Pages;

use App\Filament\Resources\ApMatchingSettings\ApMatchingSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditApMatchingSetting extends EditRecord
{
    protected static string $resource = ApMatchingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
