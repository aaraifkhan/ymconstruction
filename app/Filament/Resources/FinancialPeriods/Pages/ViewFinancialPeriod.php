<?php

namespace App\Filament\Resources\FinancialPeriods\Pages;

use App\Filament\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialPeriod extends ViewRecord
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
