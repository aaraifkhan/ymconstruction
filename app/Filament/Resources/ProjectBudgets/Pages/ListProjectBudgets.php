<?php

namespace App\Filament\Resources\ProjectBudgets\Pages;

use App\Filament\Resources\ProjectBudgets\ProjectBudgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectBudgets extends ListRecords
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
