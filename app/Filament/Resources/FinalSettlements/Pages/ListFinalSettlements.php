<?php

namespace App\Filament\Resources\FinalSettlements\Pages;

use App\Filament\Resources\FinalSettlements\FinalSettlementResource;
use Filament\Resources\Pages\ListRecords;

class ListFinalSettlements extends ListRecords
{
    protected static string $resource = FinalSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
