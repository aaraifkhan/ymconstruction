<?php

namespace App\Filament\Resources\PerformanceKpis\Pages;

use App\Filament\Resources\PerformanceKpis\PerformanceKpiResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePerformanceKpi extends CreateRecord
{
    protected static string $resource = PerformanceKpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
