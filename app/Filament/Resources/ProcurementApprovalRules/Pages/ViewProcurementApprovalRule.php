<?php

namespace App\Filament\Resources\ProcurementApprovalRules\Pages;

use App\Filament\Resources\ProcurementApprovalRules\ProcurementApprovalRuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProcurementApprovalRule extends ViewRecord
{
    protected static string $resource = ProcurementApprovalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
