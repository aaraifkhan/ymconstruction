<?php

namespace App\Filament\Resources\DailyWorkReports\Pages;

use App\Filament\Resources\DailyWorkReports\DailyWorkReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyWorkReport extends EditRecord
{
    protected static string $resource = DailyWorkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
