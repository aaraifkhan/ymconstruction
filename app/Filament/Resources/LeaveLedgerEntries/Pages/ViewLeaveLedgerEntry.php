<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Pages;

use App\Filament\Resources\LeaveLedgerEntries\LeaveLedgerEntryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaveLedgerEntry extends ViewRecord
{
    protected static string $resource = LeaveLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
