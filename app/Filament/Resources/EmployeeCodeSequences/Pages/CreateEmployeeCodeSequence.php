<?php

namespace App\Filament\Resources\EmployeeCodeSequences\Pages;

use App\Filament\Resources\EmployeeCodeSequences\EmployeeCodeSequenceResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeCodeSequence extends CreateRecord
{
    protected static string $resource = EmployeeCodeSequenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
