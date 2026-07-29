<?php

namespace App\Filament\Resources\EmploymentSeparations\Pages;

use App\Filament\Resources\EmploymentSeparations\EmploymentSeparationResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmploymentSeparation extends CreateRecord
{
    protected static string $resource = EmploymentSeparationResource::class;

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
