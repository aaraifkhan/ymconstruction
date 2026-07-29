<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Pages;

use App\Filament\Resources\LeaveLedgerEntries\LeaveLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveLedgerEntry extends CreateRecord
{
    protected static string $resource = LeaveLedgerEntryResource::class;
}
