<?php

namespace App\Filament\Resources\InventoryTransactions\Pages;

use App\Filament\Resources\InventoryTransactions\Actions\InventoryTransactionWorkflowActions;
use App\Filament\Resources\InventoryTransactions\InventoryTransactionResource;
use App\Models\InventoryTransaction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryTransaction extends ViewRecord
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (InventoryTransaction $record): bool => $record->isEditable()),
            InventoryTransactionWorkflowActions::post(),
        ];
    }
}
