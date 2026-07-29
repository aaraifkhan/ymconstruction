<?php

namespace App\Filament\Resources\ClearanceChecklistTemplates\Pages;

use App\Filament\Resources\ClearanceChecklistTemplates\ClearanceChecklistTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClearanceChecklistTemplate extends EditRecord
{
    protected static string $resource = ClearanceChecklistTemplateResource::class;

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
