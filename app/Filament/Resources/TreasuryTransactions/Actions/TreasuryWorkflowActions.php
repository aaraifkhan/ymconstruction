<?php

namespace App\Filament\Resources\TreasuryTransactions\Actions;

use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\RejectTreasuryTransactionAction;
use App\Actions\Treasury\ReverseTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\TreasuryStatus;
use App\Models\TreasuryTransaction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class TreasuryWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->authorize('submit')->color('warning')
            ->visible(fn (TreasuryTransaction $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(fn (TreasuryTransaction $record) => app(SubmitTreasuryTransactionAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->authorize('approve')->color('success')
            ->visible(fn (TreasuryTransaction $record): bool => $record->status === TreasuryStatus::Submitted)
            ->requiresConfirmation()
            ->action(fn (TreasuryTransaction $record) => app(ApproveTreasuryTransactionAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->authorize('reject')->color('danger')
            ->visible(fn (TreasuryTransaction $record): bool => in_array(
                $record->status,
                [TreasuryStatus::Submitted, TreasuryStatus::Approved],
                true,
            ))->schema([Textarea::make('reason')->required()])
            ->action(fn (TreasuryTransaction $record, array $data) => app(RejectTreasuryTransactionAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function post(): Action
    {
        return Action::make('post')->authorize('post')->color('success')
            ->visible(fn (TreasuryTransaction $record): bool => $record->status === TreasuryStatus::Approved)
            ->requiresConfirmation()
            ->action(fn (TreasuryTransaction $record) => app(PostTreasuryTransactionAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->authorize('reverse')->color('danger')
            ->visible(fn (TreasuryTransaction $record): bool => $record->status === TreasuryStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required(),
            ])->action(fn (TreasuryTransaction $record, array $data) => app(ReverseTreasuryTransactionAction::class)
            ->handle($record, Filament::auth()->user(), Carbon::parse($data['reversal_date']), $data['reason']));
    }
}
