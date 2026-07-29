<?php

namespace App\Filament\Resources\EmploymentMovementRequests\Pages;

use App\Filament\Resources\EmploymentMovementRequests\EmploymentMovementRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentMovementRequests extends ListRecords
{
    protected static string $resource = EmploymentMovementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
