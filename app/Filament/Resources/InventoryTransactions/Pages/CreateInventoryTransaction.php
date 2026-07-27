<?php

namespace App\Filament\Resources\InventoryTransactions\Pages;

use App\Enums\InventoryTransactionStatus;
use App\Filament\Resources\InventoryTransactions\InventoryTransactionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryTransaction extends CreateRecord
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'status' => InventoryTransactionStatus::Draft,
            'prepared_by_id' => Filament::auth()->id(),
        ];
    }
}
