<?php

namespace App\Filament\Resources\EmploymentMovementRequests\Pages;

use App\Filament\Resources\EmploymentMovementRequests\EmploymentMovementRequestResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmploymentMovementRequest extends CreateRecord
{
    protected static string $resource = EmploymentMovementRequestResource::class;

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
