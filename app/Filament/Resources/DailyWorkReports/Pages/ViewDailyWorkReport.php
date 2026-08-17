<?php

namespace App\Filament\Resources\DailyWorkReports\Pages;

use App\Filament\Resources\DailyWorkReports\DailyWorkReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyWorkReport extends ViewRecord
{
    protected static string $resource = DailyWorkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
