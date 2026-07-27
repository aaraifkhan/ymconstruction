<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTreasuryTransactions extends ListRecords
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
