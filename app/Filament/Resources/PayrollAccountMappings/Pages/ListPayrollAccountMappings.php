<?php

namespace App\Filament\Resources\PayrollAccountMappings\Pages;

use App\Filament\Resources\PayrollAccountMappings\PayrollAccountMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollAccountMappings extends ListRecords
{
    protected static string $resource = PayrollAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
