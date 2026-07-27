<?php

namespace App\Filament\Resources\FinancialYears\Pages;

use App\Filament\Resources\FinancialYears\FinancialYearResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialYear extends ViewRecord
{
    protected static string $resource = FinancialYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
