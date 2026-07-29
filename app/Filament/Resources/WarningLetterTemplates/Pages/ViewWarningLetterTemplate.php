<?php

namespace App\Filament\Resources\WarningLetterTemplates\Pages;

use App\Filament\Resources\WarningLetterTemplates\WarningLetterTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWarningLetterTemplate extends ViewRecord
{
    protected static string $resource = WarningLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
