<?php

namespace App\Filament\Resources\HrDocumentTypes\Pages;

use App\Filament\Resources\HrDocumentTypes\HrDocumentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHrDocumentType extends EditRecord
{
    protected static string $resource = HrDocumentTypeResource::class;

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
