<?php

namespace App\Filament\Resources\ProjectBudgets\Pages;

use App\Filament\Resources\ProjectBudgets\Actions\ProjectBudgetWorkflowActions;
use App\Filament\Resources\ProjectBudgets\ProjectBudgetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectBudget extends ViewRecord
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ProjectBudgetWorkflowActions::approve(),
        ];
    }
}
