<?php

namespace App\Filament\Resources\ProjectBudgets\Pages;

use App\Enums\ProjectBudgetStatus;
use App\Filament\Resources\ProjectBudgets\ProjectBudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectBudget extends CreateRecord
{
    protected static string $resource = ProjectBudgetResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'status' => ProjectBudgetStatus::Draft,
            'total_amount' => 0,
            'prepared_by_id' => auth()->id(),
            'approved_by_id' => null,
            'approved_at' => null,
        ];
    }
}
