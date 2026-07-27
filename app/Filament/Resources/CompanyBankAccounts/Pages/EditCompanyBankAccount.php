<?php

namespace App\Filament\Resources\CompanyBankAccounts\Pages;

use App\Filament\Resources\CompanyBankAccounts\CompanyBankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyBankAccount extends EditRecord
{
    protected static string $resource = CompanyBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
