<?php

namespace App\Filament\Resources\WarningLetterTemplates\Pages;

use App\Filament\Resources\WarningLetterTemplates\WarningLetterTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarningLetterTemplates extends ListRecords
{
    protected static string $resource = WarningLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
