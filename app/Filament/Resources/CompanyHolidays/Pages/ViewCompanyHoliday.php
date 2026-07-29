<?php

namespace App\Filament\Resources\CompanyHolidays\Pages;

use App\Filament\Resources\CompanyHolidays\CompanyHolidayResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyHoliday extends ViewRecord
{
    protected static string $resource = CompanyHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
