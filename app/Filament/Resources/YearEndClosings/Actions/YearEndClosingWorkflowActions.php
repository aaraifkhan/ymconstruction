<?php

namespace App\Filament\Resources\YearEndClosings\Actions;

use App\Actions\Accounting\ApproveYearEndClosingAction;
use App\Actions\Accounting\PostYearEndClosingAction;
use App\Actions\Accounting\ReverseYearEndClosingAction;
use App\Enums\YearEndClosingStatus;
use App\Models\YearEndClosing;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;

class YearEndClosingWorkflowActions
{
    public static function approve(): Action
    {
        return Action::make('approve')->color('success')->authorize('approve')
            ->visible(fn (YearEndClosing $record): bool => $record->status === YearEndClosingStatus::Draft)
            ->requiresConfirmation()->action(fn (YearEndClosing $record) => app(ApproveYearEndClosingAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function post(): Action
    {
        return Action::make('post')->color('success')->authorize('post')
            ->visible(fn (YearEndClosing $record): bool => $record->status === YearEndClosingStatus::Approved)
            ->requiresConfirmation()->action(fn (YearEndClosing $record) => app(PostYearEndClosingAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->color('danger')->authorize('reverse')
            ->visible(fn (YearEndClosing $record): bool => $record->status === YearEndClosingStatus::Posted)
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (YearEndClosing $record, array $data) => app(ReverseYearEndClosingAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }
}
