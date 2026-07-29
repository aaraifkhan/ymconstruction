<?php

namespace App\Filament\Resources\PerformanceKpis\Pages;

use App\Filament\Resources\PerformanceKpis\PerformanceKpiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPerformanceKpi extends ViewRecord
{
    protected static string $resource = PerformanceKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
