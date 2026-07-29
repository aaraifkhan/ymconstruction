<?php

namespace App\Filament\Resources\FinalSettlements\Pages;

use App\Filament\Resources\FinalSettlements\FinalSettlementResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFinalSettlement extends EditRecord
{
    protected static string $resource = FinalSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
