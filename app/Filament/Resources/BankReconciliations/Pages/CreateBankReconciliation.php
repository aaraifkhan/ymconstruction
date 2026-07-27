<?php

namespace App\Filament\Resources\BankReconciliations\Pages;

use App\Enums\BankReconciliationStatus;
use App\Filament\Resources\BankReconciliations\BankReconciliationResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBankReconciliation extends CreateRecord
{
    protected static string $resource = BankReconciliationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'prepared_by_id' => Filament::auth()->id(),
            'status' => BankReconciliationStatus::Draft,
        ];
    }
}
