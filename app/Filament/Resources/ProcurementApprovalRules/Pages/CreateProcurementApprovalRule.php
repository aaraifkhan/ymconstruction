<?php

namespace App\Filament\Resources\ProcurementApprovalRules\Pages;

use App\Filament\Resources\ProcurementApprovalRules\ProcurementApprovalRuleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateProcurementApprovalRule extends CreateRecord
{
    protected static string $resource = ProcurementApprovalRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey()];
    }
}
