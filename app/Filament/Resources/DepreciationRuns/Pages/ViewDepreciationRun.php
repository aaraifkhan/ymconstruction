<?php

namespace App\Filament\Resources\DepreciationRuns\Pages;

use App\Filament\Resources\DepreciationRuns\Actions\DepreciationWorkflowActions;
use App\Filament\Resources\DepreciationRuns\DepreciationRunResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDepreciationRun extends ViewRecord
{
    protected static string $resource = DepreciationRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DepreciationWorkflowActions::generate(),
            DepreciationWorkflowActions::submit(),
            DepreciationWorkflowActions::approve(),
            DepreciationWorkflowActions::post(),
            DepreciationWorkflowActions::reverse(),
        ];
    }
}
