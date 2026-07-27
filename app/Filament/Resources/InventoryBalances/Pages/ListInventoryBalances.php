<?php

namespace App\Filament\Resources\InventoryBalances\Pages;

use App\Filament\Resources\InventoryBalances\InventoryBalanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryBalances extends ListRecords
{
    protected static string $resource = InventoryBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
