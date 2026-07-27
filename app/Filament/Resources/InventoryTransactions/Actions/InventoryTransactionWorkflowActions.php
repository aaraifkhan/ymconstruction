<?php

namespace App\Filament\Resources\InventoryTransactions\Actions;

use App\Actions\Inventory\PostInventoryTransactionAction;
use App\Enums\InventoryTransactionStatus;
use App\Models\InventoryTransaction;
use Filament\Actions\Action;
use Filament\Facades\Filament;

class InventoryTransactionWorkflowActions
{
    public static function post(): Action
    {
        return Action::make('post')->authorize('post')->color('success')
            ->label('Post inventory movement')
            ->visible(fn (InventoryTransaction $record): bool => $record->status === InventoryTransactionStatus::Draft)
            ->requiresConfirmation()
            ->action(fn (InventoryTransaction $record) => app(PostInventoryTransactionAction::class)
                ->handle($record, Filament::auth()->user()));
    }
}
