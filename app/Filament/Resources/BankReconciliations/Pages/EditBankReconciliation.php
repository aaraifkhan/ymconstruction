<?php

namespace App\Filament\Resources\BankReconciliations\Pages;

use App\Enums\BankReconciliationStatus;
use App\Filament\Resources\BankReconciliations\BankReconciliationResource;
use App\Models\BankReconciliation;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBankReconciliation extends EditRecord
{
    protected static string $resource = BankReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (BankReconciliation $record): bool => $record->status === BankReconciliationStatus::Draft),
        ];
    }
}
