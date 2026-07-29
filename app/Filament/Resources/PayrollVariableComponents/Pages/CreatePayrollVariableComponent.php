<?php

namespace App\Filament\Resources\PayrollVariableComponents\Pages;

use App\Filament\Resources\PayrollVariableComponents\PayrollVariableComponentResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollVariableComponent extends CreateRecord
{
    protected static string $resource = PayrollVariableComponentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'created_by_id' => auth()->id(),
        ];
    }
}
