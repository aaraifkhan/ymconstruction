<?php

namespace App\Filament\Resources\VendorBills\Pages;

use App\Enums\VendorBillStatus;
use App\Filament\Resources\VendorBills\VendorBillResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'prepared_by_id' => Filament::auth()->id(),
            'status' => VendorBillStatus::Draft,
            'currency_code' => 'PKR',
        ];
    }
}
