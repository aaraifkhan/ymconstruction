<?php

namespace App\Filament\Resources\JoiningLetterTemplates\Pages;

use App\Filament\Resources\JoiningLetterTemplates\JoiningLetterTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJoiningLetterTemplate extends ViewRecord
{
    protected static string $resource = JoiningLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
