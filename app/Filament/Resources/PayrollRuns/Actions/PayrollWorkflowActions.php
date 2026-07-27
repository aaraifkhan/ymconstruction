<?php

namespace App\Filament\Resources\PayrollRuns\Actions;

use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\GeneratePayrollEntriesAction;
use App\Actions\Payroll\LockPayrollRunAction;
use App\Actions\Payroll\MarkPayrollRunPaidAction;
use App\Actions\Payroll\PostPayrollRunAction;
use App\Actions\Payroll\RejectPayrollRunAction;
use App\Actions\Payroll\ReversePayrollRunAction;
use App\Actions\Payroll\SubmitPayrollRunAction;
use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class PayrollWorkflowActions
{
    public static function generate(): Action
    {
        return Action::make('generateEntries')->label('Generate / Refresh Entries')->icon('heroicon-o-calculator')
            ->authorize('generateEntries')->visible(fn (PayrollRun $record): bool => in_array($record->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true))
            ->requiresConfirmation()->action(fn (PayrollRun $record, GeneratePayrollEntriesAction $action) => self::run($record, 'generateEntries', fn () => $action->handle($record, self::user()), 'Payroll entries generated'));
    }

    public static function submit(): Action
    {
        return Action::make('submit')->label('Submit for Review')->color('warning')->icon('heroicon-o-paper-airplane')
            ->authorize('submit')->visible(fn (PayrollRun $record): bool => in_array($record->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true))
            ->requiresConfirmation()->action(fn (PayrollRun $record, SubmitPayrollRunAction $action) => self::run($record, 'submit', fn () => $action->handle($record, self::user()), 'Payroll submitted'));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->color('success')->icon('heroicon-o-check-badge')->authorize('approve')
            ->visible(fn (PayrollRun $record): bool => $record->status === PayrollRunStatus::UnderReview)->requiresConfirmation()
            ->action(fn (PayrollRun $record, ApprovePayrollRunAction $action) => self::run($record, 'approve', fn () => $action->handle($record, self::user()), 'Payroll approved'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->color('danger')->icon('heroicon-o-x-circle')->authorize('reject')
            ->visible(fn (PayrollRun $record): bool => $record->status === PayrollRunStatus::UnderReview)
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(function (array $data, PayrollRun $record, RejectPayrollRunAction $action): void {
                Gate::authorize('reject', $record);
                $action->handle($record, self::user(), $data['reason']);
                self::notify('Payroll rejected');
            });
    }

    public static function markPaid(): Action
    {
        return Action::make('markPaid')->label('Mark Paid')->color('info')->icon('heroicon-o-banknotes')->authorize('markPaid')
            ->visible(fn (PayrollRun $record): bool => $record->status === PayrollRunStatus::Approved)->requiresConfirmation()
            ->action(fn (PayrollRun $record, MarkPayrollRunPaidAction $action) => self::run($record, 'markPaid', fn () => $action->handle($record, self::user()), 'Payroll marked paid'));
    }

    public static function post(): Action
    {
        return Action::make('post')->label('Post to Accounts')->color('success')->icon('heroicon-o-book-open')
            ->authorize('post')->visible(fn (PayrollRun $record): bool => $record->status === PayrollRunStatus::Approved
                && ! $record->isPostedToAccounts())
            ->requiresConfirmation()
            ->action(fn (PayrollRun $record, PostPayrollRunAction $action) => self::run(
                $record, 'post', fn () => $action->handle($record, self::user()), 'Payroll posted to Accounts',
            ));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->label('Reverse Posting')->color('danger')->icon('heroicon-o-arrow-uturn-left')
            ->authorize('reverse')->visible(fn (PayrollRun $record): bool => $record->status === PayrollRunStatus::Approved
                && $record->isPostedToAccounts())
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])->action(function (array $data, PayrollRun $record, ReversePayrollRunAction $action): void {
                Gate::authorize('reverse', $record);
                $action->handle($record, self::user(), Carbon::parse($data['reversal_date']), $data['reason']);
                self::notify('Payroll posting reversed');
            });
    }

    public static function lock(): Action
    {
        return Action::make('lock')->label('Lock Payroll')->color('primary')->icon('heroicon-o-lock-closed')->authorize('lock')
            ->visible(fn (PayrollRun $record): bool => $record->status === PayrollRunStatus::Paid)->requiresConfirmation()
            ->action(fn (PayrollRun $record, LockPayrollRunAction $action) => self::run($record, 'lock', fn () => $action->handle($record, self::user()), 'Payroll locked'));
    }

    private static function run(PayrollRun $record, string $ability, callable $action, string $message): void
    {
        Gate::authorize($ability, $record);
        $action();
        self::notify($message);
    }

    private static function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private static function notify(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }
}
