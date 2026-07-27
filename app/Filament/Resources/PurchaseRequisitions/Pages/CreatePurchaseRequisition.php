<?php

namespace App\Filament\Resources\PurchaseRequisitions\Pages;

use App\Enums\PurchaseRequisitionStatus;
use App\Filament\Resources\PurchaseRequisitions\PurchaseRequisitionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseRequisition extends CreateRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'status' => PurchaseRequisitionStatus::Draft,
            'prepared_by_id' => Filament::auth()->id(),
        ];
    }
}
