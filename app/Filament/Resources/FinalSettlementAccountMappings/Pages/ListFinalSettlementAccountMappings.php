<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings\Pages;

use App\Filament\Resources\FinalSettlementAccountMappings\FinalSettlementAccountMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinalSettlementAccountMappings extends ListRecords
{
    protected static string $resource = FinalSettlementAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
