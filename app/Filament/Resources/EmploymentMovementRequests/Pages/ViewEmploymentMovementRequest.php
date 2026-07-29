<?php

namespace App\Filament\Resources\EmploymentMovementRequests\Pages;

use App\Filament\Resources\EmploymentMovementRequests\EmploymentMovementRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmploymentMovementRequest extends ViewRecord
{
    protected static string $resource = EmploymentMovementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
