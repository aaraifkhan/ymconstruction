<?php

namespace App\Filament\Resources\DepreciationRuns\Pages;

use App\Filament\Resources\DepreciationRuns\Actions\DepreciationWorkflowActions;
use App\Filament\Resources\DepreciationRuns\DepreciationRunResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDepreciationRun extends EditRecord
{
    protected static string $resource = DepreciationRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DepreciationWorkflowActions::generate(),
            DepreciationWorkflowActions::submit(),
            DeleteAction::make(),
        ];
    }
}
