<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings\Pages;

use App\Filament\Resources\FinalSettlementAccountMappings\FinalSettlementAccountMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFinalSettlementAccountMapping extends ViewRecord
{
    protected static string $resource = FinalSettlementAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
