<?php

namespace App\Filament\Resources\BankReconciliations\Pages;

use App\Filament\Resources\BankReconciliations\BankReconciliationResource;
use Filament\Resources\Pages\ListRecords;

class ListBankReconciliations extends ListRecords
{
    protected static string $resource = BankReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
