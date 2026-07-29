<?php

namespace App\Filament\Resources\WarningLetterTemplates\Pages;

use App\Filament\Resources\WarningLetterTemplates\WarningLetterTemplateResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateWarningLetterTemplate extends CreateRecord
{
    protected static string $resource = WarningLetterTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
