<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Pages;

use App\Filament\Resources\ClearanceChecklistTemplates\ClearanceChecklistTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClearanceChecklistTemplates extends ListRecords
{
    protected static string $resource = ClearanceChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
