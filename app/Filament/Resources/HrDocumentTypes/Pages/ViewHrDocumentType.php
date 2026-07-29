<?php

namespace App\Filament\Resources\HrDocumentTypes\Pages;

use App\Filament\Resources\HrDocumentTypes\HrDocumentTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHrDocumentType extends ViewRecord
{
    protected static string $resource = HrDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
