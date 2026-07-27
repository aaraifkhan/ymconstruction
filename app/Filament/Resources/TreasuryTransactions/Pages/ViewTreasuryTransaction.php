<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\Actions\TreasuryWorkflowActions;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\TreasuryTransaction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTreasuryTransaction extends ViewRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (TreasuryTransaction $record): bool => $record->isEditable()),
            TreasuryWorkflowActions::submit(),
            TreasuryWorkflowActions::approve(),
            TreasuryWorkflowActions::reject(),
            TreasuryWorkflowActions::post(),
            TreasuryWorkflowActions::reverse(),
        ];
    }
}
