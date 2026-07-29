<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Pages;

use App\Filament\Resources\EmployeeAssetCustodies\EmployeeAssetCustodyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeAssetCustody extends ViewRecord
{
    protected static string $resource = EmployeeAssetCustodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
