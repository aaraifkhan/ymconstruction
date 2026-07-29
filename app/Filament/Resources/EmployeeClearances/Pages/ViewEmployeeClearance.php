<?php

namespace App\Filament\Resources\EmployeeClearances\Pages;

use App\Filament\Resources\EmployeeClearances\EmployeeClearanceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeClearance extends ViewRecord
{
    protected static string $resource = EmployeeClearanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
