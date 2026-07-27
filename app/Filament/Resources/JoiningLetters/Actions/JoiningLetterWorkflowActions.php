<?php

namespace App\Filament\Resources\JoiningLetters\Actions;

use App\Actions\JoiningLetters\ApproveJoiningLetterAction;
use App\Actions\JoiningLetters\IssueJoiningLetterAction;
use App\Actions\JoiningLetters\RecordJoiningLetterAcceptanceAction;
use App\Actions\JoiningLetters\RejectJoiningLetterAction;
use App\Actions\JoiningLetters\RenderJoiningLetterAction;
use App\Actions\JoiningLetters\SubmitJoiningLetterAction;
use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class JoiningLetterWorkflowActions
{
    public static function regenerate(): Action
    {
        return Action::make('regenerate')
            ->label('Regenerate from Template')
            ->icon('heroicon-o-arrow-path')
            ->authorize('regenerate')
            ->visible(fn (JoiningLetter $record): bool => in_array(
                $record->status,
                [JoiningLetterStatus::Draft, JoiningLetterStatus::Rejected],
                true,
            ))
            ->requiresConfirmation()
            ->action(function (JoiningLetter $record, RenderJoiningLetterAction $render): void {
                Gate::authorize('regenerate', $record);
                $render->handle($record, self::user());
                self::success('Joining letter regenerated');
            });
    }

    public static function submit(): Action
    {
        return Action::make('submit')
            ->label('Submit for Approval')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->authorize('submit')
            ->visible(fn (JoiningLetter $record): bool => $record->status === JoiningLetterStatus::Draft)
            ->requiresConfirmation()
            ->action(function (JoiningLetter $record, SubmitJoiningLetterAction $submit): void {
                Gate::authorize('submit', $record);
                $submit->handle($record, self::user());
                self::success('Joining letter submitted');
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->authorize('approve')
            ->visible(fn (JoiningLetter $record): bool => $record->status === JoiningLetterStatus::PendingApproval)
            ->requiresConfirmation()
            ->action(function (JoiningLetter $record, ApproveJoiningLetterAction $approve): void {
                Gate::authorize('approve', $record);
                $approve->handle($record, self::user());
                self::success('Joining letter approved');
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->authorize('reject')
            ->visible(fn (JoiningLetter $record): bool => $record->status === JoiningLetterStatus::PendingApproval)
            ->schema([
                Textarea::make('reason')->label('Rejection reason')->required()->maxLength(2000)->rows(4),
            ])
            ->action(function (array $data, JoiningLetter $record, RejectJoiningLetterAction $reject): void {
                Gate::authorize('reject', $record);
                $reject->handle($record, self::user(), $data['reason']);
                self::success('Joining letter rejected');
            });
    }

    public static function issue(): Action
    {
        return Action::make('issue')
            ->label('Issue Letter')
            ->icon('heroicon-o-envelope-open')
            ->color('info')
            ->authorize('issue')
            ->visible(fn (JoiningLetter $record): bool => $record->status === JoiningLetterStatus::Approved)
            ->requiresConfirmation()
            ->action(function (JoiningLetter $record, IssueJoiningLetterAction $issue): void {
                Gate::authorize('issue', $record);
                $issue->handle($record, self::user());
                self::success('Joining letter issued');
            });
    }

    public static function recordAcceptance(): Action
    {
        return Action::make('recordAcceptance')
            ->label('Record Acceptance')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            ->authorize('recordAcceptance')
            ->visible(fn (JoiningLetter $record): bool => $record->status === JoiningLetterStatus::Issued)
            ->schema([
                TextInput::make('accepted_by_name')
                    ->label('Accepted by employee')
                    ->default(fn (JoiningLetter $record): string => $record->employment->employee->full_name)
                    ->required()
                    ->maxLength(255),
                Textarea::make('acceptance_notes')->label('Acceptance notes')->maxLength(2000)->rows(3),
            ])
            ->action(function (
                array $data,
                JoiningLetter $record,
                RecordJoiningLetterAcceptanceAction $acceptance,
            ): void {
                Gate::authorize('recordAcceptance', $record);
                $acceptance->handle(
                    letter: $record,
                    actor: self::user(),
                    acceptedByName: $data['accepted_by_name'],
                    notes: $data['acceptance_notes'] ?? null,
                );
                self::success('Employee acceptance recorded');
            });
    }

    private static function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private static function success(string $title): void
    {
        Notification::make()->title($title)->success()->send();
    }
}
