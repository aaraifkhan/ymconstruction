<?php

namespace App\Filament\Resources\EmployeeFinancings\Pages;

use App\Filament\Resources\EmployeeFinancings\EmployeeFinancingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeFinancing extends CreateRecord
{
    protected static string $resource = EmployeeFinancingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = $this->getTenant()->getKey();
        $data['requested_by_id'] = auth()->id();
        $data['total_repayable'] = bcadd((string) $data['principal_amount'], (string) ($data['finance_charge'] ?? 0), 4);

        return $data;
    }
}
