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
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class JournalWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->icon('heroicon-o-paper-airplane')
            ->authorize('submit')
            ->color('warning')
            ->visible(fn (JournalEntry $record): bool => $record->isEditable())
            ->requiresConfirmation()
            ->action(function (JournalEntry $record): void {
                try {
                    app(SubmitJournalEntryAction::class)->handle($record, Filament::auth()->user());
                    Notification::make()
                        ->title('Voucher Submitted')
                        ->body("Voucher {$record->voucher_number} has been submitted for approval.")
                        ->success()
                        ->send();
                } catch (ValidationException $e) {
                    $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
                    Notification::make()
                        ->title('Submission Blocked')
                        ->body($firstError)
                        ->danger()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Submission Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check')
            ->authorize('approve')
            ->color('success')
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Submitted)
            ->requiresConfirmation()
            ->action(function (JournalEntry $record): void {
                try {
                    app(ApproveJournalEntryAction::class)->handle($record, Filament::auth()->user());
                    Notification::make()
                        ->title('Voucher Approved')
                        ->body("Voucher {$record->voucher_number} has been approved.")
                        ->success()
                        ->send();
                } catch (ValidationException $e) {
                    $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
                    Notification::make()
                        ->title('Approval Blocked')
                        ->body($firstError)
                        ->danger()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Approval Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-mark')
            ->authorize('reject')
            ->color('danger')
            ->visible(fn (JournalEntry $record): bool => in_array($record->status, [JournalStatus::Submitted, JournalStatus::Approved], true))
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(function (JournalEntry $record, array $data): void {
                try {
                    app(RejectJournalEntryAction::class)->handle($record, Filament::auth()->user(), $data['reason']);
                    Notification::make()
                        ->title('Voucher Rejected')
                        ->body("Voucher {$record->voucher_number} has been rejected.")
                        ->warning()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Rejection Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function post(): Action
    {
        return Action::make('post')
            ->label('Post')
            ->icon('heroicon-o-arrow-path')
            ->authorize('post')
            ->color('primary')
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Approved)
            ->requiresConfirmation()
            ->action(function (JournalEntry $record): void {
                try {
                    $posted = app(PostJournalEntryAction::class)->handle($record, Filament::auth()->user());
                    Notification::make()
                        ->title('Voucher Posted')
                        ->body("Voucher {$posted->voucher_number} posted successfully.")
                        ->success()
                        ->send();
                } catch (ValidationException $e) {
                    $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
                    Notification::make()
                        ->title('Posting Blocked')
                        ->body($firstError)
                        ->danger()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Posting Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')
            ->label('Reverse')
            ->icon('heroicon-o-arrow-uturn-left')
            ->authorize('reverse')
            ->color('danger')
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])
            ->action(function (JournalEntry $record, array $data): void {
                try {
                    $reversal = app(ReverseJournalEntryAction::class)->handle(
                        $record,
                        Filament::auth()->user(),
                        CarbonImmutable::parse($data['reversal_date']),
                        $data['reason'],
                    );
                    Notification::make()
                        ->title('Voucher Reversed')
                        ->body("Reversal voucher {$reversal->voucher_number} created.")
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Reversal Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
