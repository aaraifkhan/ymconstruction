<?php

namespace App\Filament\Resources\PayrollAccountMappings\Pages;

use App\Filament\Resources\PayrollAccountMappings\PayrollAccountMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayrollAccountMapping extends ViewRecord
{
    protected static string $resource = PayrollAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
