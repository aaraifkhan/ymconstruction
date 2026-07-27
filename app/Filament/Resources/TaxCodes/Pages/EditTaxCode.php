<?php

namespace App\Filament\Resources\TaxCodes\Pages;

use App\Filament\Resources\TaxCodes\TaxCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxCode extends EditRecord
{
    protected static string $resource = TaxCodeResource::class;

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
