<?php

namespace App\Filament\Resources\ProcurementApprovalRules\Pages;

use App\Filament\Resources\ProcurementApprovalRules\ProcurementApprovalRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProcurementApprovalRules extends ListRecords
{
    protected static string $resource = ProcurementApprovalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
