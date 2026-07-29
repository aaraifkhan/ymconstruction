<?php

namespace App\Filament\Resources\EmploymentSeparations\Pages;

use App\Filament\Resources\EmploymentSeparations\EmploymentSeparationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmploymentSeparation extends ViewRecord
{
    protected static string $resource = EmploymentSeparationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
