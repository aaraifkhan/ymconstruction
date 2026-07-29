<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Pages;

use App\Filament\Resources\LeaveLedgerEntries\LeaveLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveLedgerEntries extends ListRecords
{
    protected static string $resource = LeaveLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
