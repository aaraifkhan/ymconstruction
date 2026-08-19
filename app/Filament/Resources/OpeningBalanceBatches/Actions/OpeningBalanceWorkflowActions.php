<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Actions;

use App\Actions\Accounting\PostOpeningBalanceBatchAction;
use App\Actions\Accounting\ValidateOpeningBalanceBatchAction;
use App\Enums\OpeningBalanceStatus;
use App\Models\OpeningBalanceBatch;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class OpeningBalanceWorkflowActions
{
    public static function validate(): Action
    {
        return Action::make('validate')
            ->label('Validate')
            ->icon('heroicon-o-check-circle')
            ->authorize('validate')
            ->color('warning')
            ->visible(fn (OpeningBalanceBatch $record): bool => $record->status === OpeningBalanceStatus::Draft)
            ->requiresConfirmation()
            ->action(function (OpeningBalanceBatch $record): void {
                try {
                    app(ValidateOpeningBalanceBatchAction::class)->handle($record, Filament::auth()->user());
                    Notification::make()
                        ->title('Opening Balance Validated')
                        ->body('Opening balance batch lines are verified and balanced.')
                        ->success()
                        ->send();
                } catch (ValidationException $e) {
                    $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
                    Notification::make()
                        ->title('Validation Failed')
                        ->body($firstError)
                        ->danger()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Validation Error')
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
            ->color('success')
            ->visible(fn (OpeningBalanceBatch $record): bool => $record->status === OpeningBalanceStatus::Validated)
            ->requiresConfirmation()
            ->action(function (OpeningBalanceBatch $record): void {
                try {
                    $posted = app(PostOpeningBalanceBatchAction::class)->handle($record, Filament::auth()->user());
                    Notification::make()
                        ->title('Opening Balance Posted')
                        ->body("Opening balance posted successfully with voucher {$posted->journalEntry?->voucher_number}.")
                        ->success()
                        ->send();
                } catch (ValidationException $e) {
                    $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
                    Notification::make()
                        ->title('Posting Failed')
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
}
