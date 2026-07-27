<?php

namespace App\Filament\Resources\ApMatchingSettings\Pages;

use App\Filament\Resources\ApMatchingSettings\ApMatchingSettingResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateApMatchingSetting extends CreateRecord
{
    protected static string $resource = ApMatchingSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
