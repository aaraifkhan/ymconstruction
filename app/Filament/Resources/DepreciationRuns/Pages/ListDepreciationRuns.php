<?php

namespace App\Filament\Resources\DepreciationRuns\Pages;

use App\Filament\Resources\DepreciationRuns\DepreciationRunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepreciationRuns extends ListRecords
{
    protected static string $resource = DepreciationRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
