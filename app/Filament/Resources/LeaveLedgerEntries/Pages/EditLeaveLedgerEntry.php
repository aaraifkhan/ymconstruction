<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Pages;

use App\Filament\Resources\LeaveLedgerEntries\LeaveLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaveLedgerEntry extends EditRecord
{
    protected static string $resource = LeaveLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
