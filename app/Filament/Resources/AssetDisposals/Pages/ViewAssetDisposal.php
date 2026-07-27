<?php

namespace App\Filament\Resources\AssetDisposals\Pages;

use App\Filament\Resources\AssetDisposals\Actions\AssetDisposalWorkflowActions;
use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetDisposal extends ViewRecord
{
    protected static string $resource = AssetDisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            AssetDisposalWorkflowActions::approve(),
            AssetDisposalWorkflowActions::post(),
            AssetDisposalWorkflowActions::reverse(),
        ];
    }
}
