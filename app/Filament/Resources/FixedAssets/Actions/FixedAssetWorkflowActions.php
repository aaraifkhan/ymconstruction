<?php

namespace App\Filament\Resources\FixedAssets\Actions;

use App\Actions\Assets\ApproveFixedAssetAction;
use App\Actions\Assets\CapitalizeFixedAssetAction;
use App\Actions\Assets\RejectFixedAssetAction;
use App\Actions\Assets\SubmitFixedAssetAction;
use App\Actions\Assets\TransferFixedAssetAction;
use App\Enums\AssetStatus;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class FixedAssetWorkflowActions
{
    public static function submit(): Action
    {
        return Action::make('submit')->label('Submit for approval')->color('warning')->authorize('submit')
            ->visible(fn (FixedAsset $record): bool => in_array($record->status, [AssetStatus::Draft, AssetStatus::Rejected], true))
            ->requiresConfirmation()->action(fn (FixedAsset $record) => app(SubmitFixedAssetAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function approve(): Action
    {
        return Action::make('approve')->color('success')->authorize('approve')
            ->visible(fn (FixedAsset $record): bool => $record->status === AssetStatus::Submitted)
            ->requiresConfirmation()->action(fn (FixedAsset $record) => app(ApproveFixedAssetAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function capitalize(): Action
    {
        return Action::make('capitalize')->label('Capitalize / Activate')->color('success')->authorize('capitalize')
            ->visible(fn (FixedAsset $record): bool => $record->status === AssetStatus::Approved)
            ->requiresConfirmation()->action(fn (FixedAsset $record) => app(CapitalizeFixedAssetAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reject(): Action
    {
        return Action::make('reject')->color('danger')->authorize('reject')
            ->visible(fn (FixedAsset $record): bool => $record->status === AssetStatus::Submitted)
            ->schema([Textarea::make('reason')->required()->maxLength(2000)])
            ->action(fn (FixedAsset $record, array $data) => app(RejectFixedAssetAction::class)
                ->handle($record, Filament::auth()->user(), $data['reason']));
    }

    public static function transfer(): Action
    {
        return Action::make('transfer')->color('info')->authorize('transfer')
            ->visible(fn (FixedAsset $record): bool => $record->status === AssetStatus::Active)
            ->schema([
                DatePicker::make('effective_on')->default(today())->required(),
                TextInput::make('location')->required()->maxLength(255),
                Textarea::make('reason')->required()->maxLength(2000),
            ])->action(fn (FixedAsset $record, array $data) => app(TransferFixedAssetAction::class)->handle(
                $record,
                Filament::auth()->user(),
                Carbon::parse($data['effective_on']),
                $data['reason'],
                [
                    'custodian_employment_id' => $record->custodian_employment_id,
                    'project_id' => $record->project_id,
                    'project_site_id' => $record->project_site_id,
                    'cost_center_id' => $record->cost_center_id,
                    'location' => $data['location'],
                ],
            ));
    }
}
