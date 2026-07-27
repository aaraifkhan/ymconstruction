<?php

namespace App\Filament\Resources\ApMatchingSettings\Pages;

use App\Filament\Resources\ApMatchingSettings\ApMatchingSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApMatchingSettings extends ListRecords
{
    protected static string $resource = ApMatchingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
