<?php

namespace App\Filament\Resources\AccountTemplates\Pages;

use App\Filament\Resources\AccountTemplates\AccountTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountTemplate extends ViewRecord
{
    protected static string $resource = AccountTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
