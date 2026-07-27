<?php

namespace App\Filament\Resources\CompanyBankAccounts\Pages;

use App\Filament\Resources\CompanyBankAccounts\CompanyBankAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyBankAccount extends ViewRecord
{
    protected static string $resource = CompanyBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
