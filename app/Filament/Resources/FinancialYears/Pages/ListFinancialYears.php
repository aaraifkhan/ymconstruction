<?php

namespace App\Filament\Resources\FinancialYears\Pages;

use App\Filament\Resources\FinancialYears\FinancialYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialYears extends ListRecords
{
    protected static string $resource = FinancialYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
