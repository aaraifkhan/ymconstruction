<?php

namespace App\Filament\Resources\WorkLocations\Pages;

use App\Filament\Resources\WorkLocations\WorkLocationResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkLocation extends CreateRecord
{
    protected static string $resource = WorkLocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
