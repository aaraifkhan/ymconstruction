<?php

namespace App\Filament\Resources\InventoryTransactions\Pages;

use App\Filament\Resources\InventoryTransactions\InventoryTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryTransaction extends EditRecord
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
