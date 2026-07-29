<?php

namespace App\Filament\Resources\FinalSettlements\Pages;

use App\Filament\Resources\FinalSettlements\FinalSettlementResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFinalSettlement extends ViewRecord
{
    protected static string $resource = FinalSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
