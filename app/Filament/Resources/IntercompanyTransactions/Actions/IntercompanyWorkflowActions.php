<?php

namespace App\Filament\Resources\IntercompanyTransactions\Actions;

use App\Actions\Accounting\ApproveIntercompanyTransactionAction;
use App\Actions\Accounting\PostIntercompanyTransactionAction;
use App\Actions\Accounting\RejectIntercompanyTransactionAction;
use App\Actions\Accounting\ReverseIntercompanyTransactionAction;
use App\Actions\Accounting\SubmitIntercompanyTransactionAction;
use App\Enums\IntercompanyStatus;
use App\Models\IntercompanyTransaction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class IntercompanyWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->color('warning')->authorize('submit')
            ->visible(fn (IntercompanyTransaction $record): bool => in_array($record->status, [IntercompanyStatus::Draft, IntercompanyStatus::Rejected], true))
            ->requiresConfirmation()->action(fn (IntercompanyTransaction $record) => app(SubmitIntercompanyTransactionAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function approveOrigin(): Action
    {
        return Action::make('approveOrigin')->label('Approve current company')->color('success')->authorize('approveOrigin')
            ->visible(fn (IntercompanyTransaction $record): bool => $record->status === IntercompanyStatus::PendingApprovals
                && (int) Filament::getTenant()?->getKey() === (int) $record->company_id
                && $record->origin_approved_by_id === null)
            ->requiresConfirmation()->action(fn (IntercompanyTransaction $record) => app(ApproveIntercompanyTransactionAction::class)->handleOrigin($record, Filament::auth()->user()));
    }

    public static function approveCounterparty(): Action
    {
        return Action::make('approveCounterparty')->label('Approve current company')->color('success')->authorize('approveCounterparty')
            ->visible(fn (IntercompanyTransaction $record): bool => $record->status === IntercompanyStatus::PendingApprovals
                && (int) Filament::getTenant()?->getKey() === (int) $record->counterparty_company_id
                && $record->counterparty_approved_by_id === null)
            ->requiresConfirmation()->action(fn (IntercompanyTransaction $record) => app(ApproveIntercompanyTransactionAction::class)->handleCounterparty($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->color('danger')->authorize('reject')
            ->visible(fn (IntercompanyTransaction $record): bool => in_array($record->status, [IntercompanyStatus::PendingApprovals, IntercompanyStatus::Approved], true))
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (IntercompanyTransaction $record, array $data) => app(RejectIntercompanyTransactionAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function post(): Action
    {
        return Action::make('post')->color('success')->authorize('post')
            ->visible(fn (IntercompanyTransaction $record): bool => $record->status === IntercompanyStatus::Approved)
            ->requiresConfirmation()->action(fn (IntercompanyTransaction $record) => app(PostIntercompanyTransactionAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->color('danger')->authorize('reverse')
            ->visible(fn (IntercompanyTransaction $record): bool => $record->status === IntercompanyStatus::Posted)
            ->schema([
                DatePicker::make('date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])->action(fn (IntercompanyTransaction $record, array $data) => app(ReverseIntercompanyTransactionAction::class)
            ->handle($record, Filament::auth()->user(), Carbon::parse($data['date']), $data['reason']));
    }
}
