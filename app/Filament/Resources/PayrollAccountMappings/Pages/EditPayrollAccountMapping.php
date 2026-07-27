<?php

namespace App\Filament\Resources\PayrollAccountMappings\Pages;

use App\Filament\Resources\PayrollAccountMappings\PayrollAccountMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollAccountMapping extends EditRecord
{
    protected static string $resource = PayrollAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
