<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Pages;

use App\Filament\Resources\EmployeeAssetCustodies\EmployeeAssetCustodyResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeAssetCustody extends CreateRecord
{
    protected static string $resource = EmployeeAssetCustodyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'prepared_by_id' => auth()->id(),
            'status' => 'draft',
        ];
    }
}
