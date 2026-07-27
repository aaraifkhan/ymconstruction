<?php

namespace App\Filament\Resources\AssetDisposals\Pages;

use App\Filament\Resources\AssetDisposals\Actions\AssetDisposalWorkflowActions;
use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetDisposal extends EditRecord
{
    protected static string $resource = AssetDisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            AssetDisposalWorkflowActions::approve(),
            DeleteAction::make(),
        ];
    }
}
