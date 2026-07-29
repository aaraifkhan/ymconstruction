<?php

namespace App\Filament\Resources\WarningLetterTemplates\Pages;

use App\Filament\Resources\WarningLetterTemplates\WarningLetterTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWarningLetterTemplate extends EditRecord
{
    protected static string $resource = WarningLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
