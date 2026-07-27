<?php

namespace App\Filament\Resources\ProcurementApprovalRules\Pages;

use App\Filament\Resources\ProcurementApprovalRules\ProcurementApprovalRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProcurementApprovalRule extends EditRecord
{
    protected static string $resource = ProcurementApprovalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
