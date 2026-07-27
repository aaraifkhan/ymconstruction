<?php

namespace App\Filament\Resources\FinancialPeriods\Actions;

use App\Actions\Accounting\CloseFinancialPeriodAction;
use App\Actions\Accounting\LockFinancialPeriodAction;
use App\Actions\Accounting\ReopenFinancialPeriodAction;
use App\Enums\FinancialPeriodStatus;
use App\Models\FinancialPeriod;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;

class FinancialPeriodWorkflowActions
{
    public static function close(): Action
    {
        return Action::make('close')->color('warning')->authorize('close')
            ->visible(fn (FinancialPeriod $record): bool => $record->status === FinancialPeriodStatus::Open)
            ->requiresConfirmation()
            ->action(fn (FinancialPeriod $record) => app(CloseFinancialPeriodAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function lock(): Action
    {
        return Action::make('lock')->color('danger')->authorize('lock')
            ->visible(fn (FinancialPeriod $record): bool => $record->status === FinancialPeriodStatus::Closed)
            ->requiresConfirmation()
            ->action(fn (FinancialPeriod $record) => app(LockFinancialPeriodAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reopen(): Action
    {
        return Action::make('reopen')->color('info')->authorize('reopen')
            ->visible(fn (FinancialPeriod $record): bool => $record->status !== FinancialPeriodStatus::Open)
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (FinancialPeriod $record, array $data) => app(ReopenFinancialPeriodAction::class)->handle($record, Filament::auth()->user(), $data['reason']));
    }
}
