<?php

namespace App\Filament\Resources\AssetDisposals\Actions;

use App\Actions\Assets\ApproveAssetDisposalAction;
use App\Actions\Assets\PostAssetDisposalAction;
use App\Actions\Assets\ReverseAssetDisposalAction;
use App\Enums\AssetAccountingStatus;
use App\Models\AssetDisposal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class AssetDisposalWorkflowActions
{
    public static function approve(): Action
    {
        return Action::make('approve')->color('success')->authorize('approve')
            ->visible(fn (AssetDisposal $record): bool => $record->status === AssetAccountingStatus::Draft)
            ->requiresConfirmation()->action(fn (AssetDisposal $record) => app(ApproveAssetDisposalAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function post(): Action
    {
        return Action::make('post')->label('Post disposal')->color('success')->authorize('post')
            ->visible(fn (AssetDisposal $record): bool => $record->status === AssetAccountingStatus::Approved)
            ->requiresConfirmation()->action(fn (AssetDisposal $record) => app(PostAssetDisposalAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->color('danger')->authorize('reverse')
            ->visible(fn (AssetDisposal $record): bool => $record->status === AssetAccountingStatus::Posted)
            ->schema([
                DatePicker::make('reversal_date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])->action(fn (AssetDisposal $record, array $data) => app(ReverseAssetDisposalAction::class)->handle(
                $record, Filament::auth()->user(), Carbon::parse($data['reversal_date']), $data['reason'],
            ));
    }
}
