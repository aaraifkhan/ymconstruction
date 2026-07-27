<?php

namespace App\Filament\Resources\JournalEntries\Actions;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\RejectJournalEntryAction;
use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\JournalStatus;
use App\Models\JournalEntry;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class JournalWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->authorize('submit')->color('warning')
            ->visible(fn (JournalEntry $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(fn (JournalEntry $record) => app(SubmitJournalEntryAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->authorize('approve')->color('success')
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Submitted)
            ->requiresConfirmation()
            ->action(fn (JournalEntry $record) => app(ApproveJournalEntryAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->authorize('reject')->color('danger')
            ->visible(fn (JournalEntry $record): bool => in_array($record->status, [JournalStatus::Submitted, JournalStatus::Approved], true))
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (JournalEntry $record, array $data) => app(RejectJournalEntryAction::class)->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function post(): Action
    {
        return Action::make('post')->authorize('post')->color('primary')
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Approved)
            ->requiresConfirmation()
            ->action(fn (JournalEntry $record) => app(PostJournalEntryAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->authorize('reverse')->color('danger')
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])
            ->action(fn (JournalEntry $record, array $data) => app(ReverseJournalEntryAction::class)->handle(
                $record, Filament::auth()->user(), CarbonImmutable::parse($data['reversal_date']), $data['reason'],
            ));
    }
}
