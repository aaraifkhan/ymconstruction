<?php

namespace App\Filament\Resources\FinancialPeriods\Pages;

use App\Filament\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialPeriod extends EditRecord
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
