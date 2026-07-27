<?php

namespace App\Filament\Resources\ProjectSites\Pages;

use App\Filament\Resources\ProjectSites\ProjectSiteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectSite extends ViewRecord
{
    protected static string $resource = ProjectSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
