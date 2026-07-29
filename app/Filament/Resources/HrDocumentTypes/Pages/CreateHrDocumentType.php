<?php

namespace App\Filament\Resources\HrDocumentTypes\Pages;

use App\Filament\Resources\HrDocumentTypes\HrDocumentTypeResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateHrDocumentType extends CreateRecord
{
    protected static string $resource = HrDocumentTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
