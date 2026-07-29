<?php

namespace App\Filament\Resources\EmployeeWarnings\Pages;

use App\Filament\Resources\EmployeeWarnings\EmployeeWarningResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeWarning extends CreateRecord
{
    protected static string $resource = EmployeeWarningResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'created_by_id' => auth()->id(),
            'status' => 'draft',
        ];
    }
}
