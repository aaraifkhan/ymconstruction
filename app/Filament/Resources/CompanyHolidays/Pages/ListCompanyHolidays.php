<?php

namespace App\Filament\Resources\CompanyHolidays\Pages;

use App\Filament\Resources\CompanyHolidays\CompanyHolidayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyHolidays extends ListRecords
{
    protected static string $resource = CompanyHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
