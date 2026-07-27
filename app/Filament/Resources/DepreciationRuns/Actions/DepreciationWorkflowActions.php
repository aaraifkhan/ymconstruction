<?php

namespace App\Filament\Resources\DepreciationRuns\Actions;

use App\Actions\Assets\ApproveDepreciationRunAction;
use App\Actions\Assets\GenerateDepreciationRunAction;
use App\Actions\Assets\PostDepreciationRunAction;
use App\Actions\Assets\ReverseDepreciationRunAction;
use App\Actions\Assets\SubmitDepreciationRunAction;
use App\Enums\AssetAccountingStatus;
use App\Models\DepreciationRun;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class DepreciationWorkflowActions
{
    public static function generate(): Action
    {
        return Action::make('generate')->label('Generate schedule')->authorize('generate')
            ->visible(fn (DepreciationRun $record): bool => $record->status === AssetAccountingStatus::Draft)
            ->requiresConfirmation()->action(fn (DepreciationRun $record) => app(GenerateDepreciationRunAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function submit(): Action
    {
        return Action::make('submit')->color('warning')->authorize('submit')
            ->visible(fn (DepreciationRun $record): bool => $record->status === AssetAccountingStatus::Draft)
            ->requiresConfirmation()->action(fn (DepreciationRun $record) => app(SubmitDepreciationRunAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->color('success')->authorize('approve')
            ->visible(fn (DepreciationRun $record): bool => $record->status === AssetAccountingStatus::Submitted)
            ->requiresConfirmation()->action(fn (DepreciationRun $record) => app(ApproveDepreciationRunAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function post(): Action
    {
        return Action::make('post')->label('Post to Accounts')->color('success')->authorize('post')
            ->visible(fn (DepreciationRun $record): bool => $record->status === AssetAccountingStatus::Approved)
            ->requiresConfirmation()->action(fn (DepreciationRun $record) => app(PostDepreciationRunAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->color('danger')->authorize('reverse')
            ->visible(fn (DepreciationRun $record): bool => $record->status === AssetAccountingStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])->action(fn (DepreciationRun $record, array $data) => app(ReverseDepreciationRunAction::class)->handle(
                $record, Filament::auth()->user(), Carbon::parse($data['reversal_date']), $data['reason'],
            ));
    }
}
