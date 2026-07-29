<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings\Pages;

use App\Filament\Resources\FinalSettlementAccountMappings\FinalSettlementAccountMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFinalSettlementAccountMapping extends EditRecord
{
    protected static string $resource = FinalSettlementAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
