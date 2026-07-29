<?php

namespace App\Filament\Resources\HrDocumentTypes\Pages;

use App\Filament\Resources\HrDocumentTypes\HrDocumentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrDocumentTypes extends ListRecords
{
    protected static string $resource = HrDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
