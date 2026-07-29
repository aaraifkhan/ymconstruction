<?php

namespace App\Filament\Resources\PerformanceKpis\Pages;

use App\Filament\Resources\PerformanceKpis\PerformanceKpiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPerformanceKpi extends EditRecord
{
    protected static string $resource = PerformanceKpiResource::class;

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
