<?php

namespace App\Filament\Resources\UnitOfMeasures\Pages;

use App\Filament\Resources\UnitOfMeasures\UnitOfMeasureResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUnitOfMeasure extends EditRecord
{
    protected static string $resource = UnitOfMeasureResource::class;

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
