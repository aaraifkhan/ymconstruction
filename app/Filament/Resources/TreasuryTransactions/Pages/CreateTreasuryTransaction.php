<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Enums\TreasuryStatus;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateTreasuryTransaction extends CreateRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'prepared_by_id' => Filament::auth()->id(),
            'status' => TreasuryStatus::Draft,
            'currency_code' => 'PKR',
        ];
    }
}
