<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Pages;

use App\Filament\Resources\EmployeeAssetCustodies\EmployeeAssetCustodyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeAssetCustody extends EditRecord
{
    protected static string $resource = EmployeeAssetCustodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
