<?php

namespace App\Filament\Resources\FixedAssets\Pages;

use App\Filament\Resources\FixedAssets\Actions\FixedAssetWorkflowActions;
use App\Filament\Resources\FixedAssets\FixedAssetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            FixedAssetWorkflowActions::submit(),
            FixedAssetWorkflowActions::approve(),
            FixedAssetWorkflowActions::reject(),
            FixedAssetWorkflowActions::capitalize(),
            FixedAssetWorkflowActions::transfer(),
        ];
    }
}
