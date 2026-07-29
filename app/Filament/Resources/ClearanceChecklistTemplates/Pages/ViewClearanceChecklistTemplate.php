<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Pages;

use App\Filament\Resources\ClearanceChecklistTemplates\ClearanceChecklistTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClearanceChecklistTemplate extends ViewRecord
{
    protected static string $resource = ClearanceChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
