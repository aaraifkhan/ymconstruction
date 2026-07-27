<?php

namespace App\Filament\Resources\ItemCategories\Pages;

use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItemCategory extends ViewRecord
{
    protected static string $resource = ItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
