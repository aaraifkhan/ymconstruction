<?php

namespace App\Filament\Resources\PerformanceKpis\Pages;

use App\Filament\Resources\PerformanceKpis\PerformanceKpiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerformanceKpis extends ListRecords
{
    protected static string $resource = PerformanceKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
