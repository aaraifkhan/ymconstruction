<?php

namespace App\Filament\Resources\IntercompanyTransactions\Pages;

use App\Filament\Resources\IntercompanyTransactions\IntercompanyTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntercompanyTransactions extends ListRecords
{
    protected static string $resource = IntercompanyTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
