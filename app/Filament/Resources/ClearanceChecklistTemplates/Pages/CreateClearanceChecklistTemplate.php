<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Pages;

use App\Filament\Resources\ClearanceChecklistTemplates\ClearanceChecklistTemplateResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateClearanceChecklistTemplate extends CreateRecord
{
    protected static string $resource = ClearanceChecklistTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
