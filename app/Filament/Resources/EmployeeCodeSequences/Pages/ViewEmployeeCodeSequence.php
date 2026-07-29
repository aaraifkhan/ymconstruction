<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Pages;

use App\Filament\Resources\EmployeeCodeSequences\EmployeeCodeSequenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeCodeSequence extends ViewRecord
{
    protected static string $resource = EmployeeCodeSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
