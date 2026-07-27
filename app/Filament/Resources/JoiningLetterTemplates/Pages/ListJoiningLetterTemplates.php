<?php

namespace App\Filament\Resources\JoiningLetterTemplates\Pages;

use App\Filament\Resources\JoiningLetterTemplates\JoiningLetterTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJoiningLetterTemplates extends ListRecords
{
    protected static string $resource = JoiningLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
