<?php

namespace App\Filament\Resources\EmploymentCompensation\Pages;

use App\Filament\Resources\EmploymentCompensation\EmploymentCompensationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentCompensation extends ListRecords
{
    protected static string $resource = EmploymentCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
