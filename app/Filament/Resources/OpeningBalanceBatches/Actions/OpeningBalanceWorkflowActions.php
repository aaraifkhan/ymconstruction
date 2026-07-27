<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Actions;

use App\Actions\Accounting\PostOpeningBalanceBatchAction;
use App\Actions\Accounting\ValidateOpeningBalanceBatchAction;
use App\Enums\OpeningBalanceStatus;
use App\Models\OpeningBalanceBatch;
use Filament\Actions\Action;
use Filament\Facades\Filament;

class OpeningBalanceWorkflowActions
{
    public static function validate(): Action
    {
        return Action::make('validate')->authorize('validate')->color('warning')
            ->visible(fn (OpeningBalanceBatch $record): bool => $record->status === OpeningBalanceStatus::Draft)
            ->requiresConfirmation()
            ->action(fn (OpeningBalanceBatch $record) => app(ValidateOpeningBalanceBatchAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function post(): Action
    {
        return Action::make('post')->authorize('post')->color('success')
            ->visible(fn (OpeningBalanceBatch $record): bool => $record->status === OpeningBalanceStatus::Validated)
            ->requiresConfirmation()
            ->action(fn (OpeningBalanceBatch $record) => app(PostOpeningBalanceBatchAction::class)->handle($record, Filament::auth()->user()));
    }
}
