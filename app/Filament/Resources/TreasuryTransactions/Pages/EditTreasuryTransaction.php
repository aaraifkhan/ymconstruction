<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\TreasuryTransaction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTreasuryTransaction extends EditRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (TreasuryTransaction $record): bool => $record->isEditable()),
        ];
    }
}
