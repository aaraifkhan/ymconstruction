<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Pages;

use App\Filament\Resources\EmployeeAssetCustodies\EmployeeAssetCustodyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAssetCustodies extends ListRecords
{
    protected static string $resource = EmployeeAssetCustodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
