<?php

namespace App\Filament\Resources\EmploymentCompensation\Actions;

use App\Actions\Compensation\ApproveEmploymentCompensationAction;
use App\Actions\Compensation\RejectEmploymentCompensationAction;
use App\Actions\Compensation\SubmitEmploymentCompensationAction;
use App\Enums\CompensationStatus;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class EmploymentCompensationWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')
            ->label('Submit for Approval')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->authorize('submit')
            ->visible(fn (EmploymentCompensation $record): bool => in_array(
                $record->status,
                [CompensationStatus::Draft, CompensationStatus::Rejected],
                true,
            ))
            ->requiresConfirmation()
            ->action(function (
                EmploymentCompensation $record,
                SubmitEmploymentCompensationAction $submit,
            ): void {
                Gate::authorize('submit', $record);
                $submit->handle($record, self::user());
                self::success('Compensation submitted');
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->authorize('approve')
            ->visible(fn (EmploymentCompensation $record): bool => $record->status === CompensationStatus::PendingApproval)
            ->requiresConfirmation()
            ->modalDescription('Approval may automatically close the employee’s previous active compensation period.')
            ->action(function (
                EmploymentCompensation $record,
                ApproveEmploymentCompensationAction $approve,
            ): void {
                Gate::authorize('approve', $record);
                $approve->handle($record, self::user());
                self::success('Compensation approved');
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->authorize('reject')
            ->visible(fn (EmploymentCompensation $record): bool => $record->status === CompensationStatus::PendingApproval)
            ->schema([
                Textarea::make('reason')
                    ->label('Rejection reason')
                    ->required()
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->action(function (
                array $data,
                EmploymentCompensation $record,
                RejectEmploymentCompensationAction $reject,
            ): void {
                Gate::authorize('reject', $record);
                $reject->handle($record, self::user(), $data['reason']);
                self::success('Compensation rejected');
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
