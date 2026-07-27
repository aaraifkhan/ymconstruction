<?php

namespace App\Filament\Resources\DocumentCategories\Pages;

use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentCategory extends ViewRecord
{
    protected static string $resource = DocumentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
