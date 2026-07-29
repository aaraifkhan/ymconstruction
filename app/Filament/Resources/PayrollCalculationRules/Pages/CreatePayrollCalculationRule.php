<?php

namespace App\Filament\Resources\PayrollCalculationRules\Pages;

use App\Filament\Resources\PayrollCalculationRules\PayrollCalculationRuleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollCalculationRule extends CreateRecord
{
    protected static string $resource = PayrollCalculationRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
