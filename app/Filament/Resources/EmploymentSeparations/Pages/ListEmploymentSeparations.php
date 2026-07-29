<?php

namespace App\Filament\Resources\EmploymentSeparations\Pages;

use App\Filament\Resources\EmploymentSeparations\EmploymentSeparationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentSeparations extends ListRecords
{
    protected static string $resource = EmploymentSeparationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
