<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Enums\BankStatementStatus;
use App\Filament\Resources\BankStatements\BankStatementResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBankStatement extends CreateRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'status' => BankStatementStatus::Draft,
            'currency_code' => 'PKR',
        ];
    }
}
