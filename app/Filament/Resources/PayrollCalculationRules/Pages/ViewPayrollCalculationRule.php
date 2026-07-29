<?php

namespace App\Filament\Resources\PayrollCalculationRules\Pages;

use App\Filament\Resources\PayrollCalculationRules\PayrollCalculationRuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayrollCalculationRule extends ViewRecord
{
    protected static string $resource = PayrollCalculationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
