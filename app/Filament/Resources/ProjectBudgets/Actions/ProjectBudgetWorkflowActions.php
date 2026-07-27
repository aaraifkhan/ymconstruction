<?php

namespace App\Filament\Resources\ProjectBudgets\Actions;

use App\Actions\Projects\ApproveProjectBudgetAction;
use App\Enums\ProjectBudgetStatus;
use App\Models\ProjectBudget;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class ProjectBudgetWorkflowActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve Budget')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->authorize('approve')
            ->visible(fn (ProjectBudget $record): bool => $record->status === ProjectBudgetStatus::Draft)
            ->requiresConfirmation()
            ->modalDescription('Approval freezes this version and supersedes any previously approved budget for the project.')
            ->action(function (ProjectBudget $record, ApproveProjectBudgetAction $approve): void {
                Gate::authorize('approve', $record);
                $approve->handle($record, self::user());

                Notification::make()
                    ->title('Project budget approved')
                    ->success()
                    ->send();
            });
    }

    private static function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
