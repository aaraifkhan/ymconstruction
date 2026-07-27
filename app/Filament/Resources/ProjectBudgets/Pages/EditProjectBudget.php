<?php

namespace App\Filament\Resources\ProjectBudgets\Pages;

use App\Filament\Resources\ProjectBudgets\ProjectBudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectBudget extends EditRecord
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
