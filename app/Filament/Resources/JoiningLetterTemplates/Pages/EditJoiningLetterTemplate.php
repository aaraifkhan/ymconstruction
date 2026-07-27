<?php

namespace App\Filament\Resources\JoiningLetterTemplates\Pages;

use App\Filament\Resources\JoiningLetterTemplates\JoiningLetterTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditJoiningLetterTemplate extends EditRecord
{
    protected static string $resource = JoiningLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
