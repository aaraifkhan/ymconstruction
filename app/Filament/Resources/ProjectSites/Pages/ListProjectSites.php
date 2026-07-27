<?php

namespace App\Filament\Resources\ProjectSites\Pages;

use App\Filament\Resources\ProjectSites\ProjectSiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectSites extends ListRecords
{
    protected static string $resource = ProjectSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
