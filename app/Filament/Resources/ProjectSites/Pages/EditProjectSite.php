<?php

namespace App\Filament\Resources\ProjectSites\Pages;

use App\Filament\Resources\ProjectSites\ProjectSiteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectSite extends EditRecord
{
    protected static string $resource = ProjectSiteResource::class;

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
