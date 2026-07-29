<?php

namespace App\Filament\Resources\PayrollCalculationRules\Pages;

use App\Filament\Resources\PayrollCalculationRules\PayrollCalculationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollCalculationRules extends ListRecords
{
    protected static string $resource = PayrollCalculationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
