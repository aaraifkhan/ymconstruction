<?php

namespace App\Filament\Resources\PayrollCalculationRules\Pages;

use App\Filament\Resources\PayrollCalculationRules\PayrollCalculationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollCalculationRule extends EditRecord
{
    protected static string $resource = PayrollCalculationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
